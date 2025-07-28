<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob;

use AzureOss\Storage\Blob\Exceptions\BlobStorageExceptionDeserializer;
use AzureOss\Storage\Blob\Exceptions\InvalidConnectionStringException;
use AzureOss\Storage\Blob\Exceptions\UnableToGenerateSasException;
use AzureOss\Storage\Blob\Helpers\BlobUriParserHelper;
use AzureOss\Storage\Blob\Models\BlobContainer;
use AzureOss\Storage\Blob\Models\TaggedBlob;
use AzureOss\Storage\Blob\Responses\FindBlobsByTagBody;
use AzureOss\Storage\Blob\Responses\ListContainersResponseBody;
use AzureOss\Storage\Common\Auth\CredentialInterface;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Common\Auth\WorkloadIdentityCredential;
use AzureOss\Storage\Common\Helpers\ConnectionStringHelper;
use AzureOss\Storage\Common\Middleware\ClientFactory;
use AzureOss\Storage\Common\Sas\AccountSasBuilder;
use AzureOss\Storage\Common\Sas\AccountSasServices;
use AzureOss\Storage\Common\Sas\SasProtocol;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

final class BlobServiceClient
{
    private readonly Client $client;

    public function __construct(
        public UriInterface $uri,
        public readonly ?CredentialInterface $credentials = null,
    ) {
        // must always include the forward slash (/) to separate the host name from the path and query portions of the URI.
        $this->uri = $uri->withPath(rtrim($uri->getPath(), '/') . "/");
        $this->client = (new ClientFactory())->create($this->uri, $credentials, new BlobStorageExceptionDeserializer());
    }

    public static function fromConnectionString(string $connectionString): self
    {
        $uri = ConnectionStringHelper::getBlobEndpoint($connectionString);
        if ($uri === null) {
            throw new InvalidConnectionStringException();
        }

        $sas = ConnectionStringHelper::getSas($connectionString);
        if ($sas !== null) {
            return new self($uri->withQuery($sas));
        }

        $accountName = ConnectionStringHelper::getAccountName($connectionString);
        $accountKey = ConnectionStringHelper::getAccountKey($connectionString);
        if ($accountName !== null && $accountKey !== null) {
            return new self($uri, new StorageSharedKeyCredential($accountName, $accountKey));
        }

        throw new InvalidConnectionStringException();
    }

    /**
     * Create BlobServiceClient using Azure Workload Identity
     */
    public static function fromWorkloadIdentity(string $storageAccountName): self
    {
        $uri = new Uri("https://$storageAccountName.blob.core.windows.net/");
        $credential = WorkloadIdentityCredential::fromEnvironment($storageAccountName);
        
        return new self($uri, $credential);
    }

    /**
     * Create BlobServiceClient using Azure Managed Identity (alias for Workload Identity)
     */
    public static function fromManagedIdentity(string $storageAccountName): self
    {
        return self::fromWorkloadIdentity($storageAccountName);
    }

    public function getContainerClient(string $containerName): BlobContainerClient
    {
        return new BlobContainerClient(
            $this->uri->withPath($this->uri->getPath() . $containerName),
            $this->credentials,
        );
    }

    /**
     * @return \Generator<BlobContainer>
     */
    public function getBlobContainers(?string $prefix = null): \Generator
    {
        $nextMarker = "";

        while (true) {
            $response = $this->client->get($this->uri, [
                'query' => [
                    'comp' => 'list',
                    'marker' => $nextMarker !== "" ? $nextMarker : null,
                    'prefix' => $prefix,
                ],
            ]);
            $body = ListContainersResponseBody::fromXml(new \SimpleXMLElement($response->getBody()->getContents()));
            $nextMarker = $body->nextMarker;

            foreach ($body->containers as $container) {
                yield $container;
            }

            if ($nextMarker === "") {
                break;
            }
        }
    }


    /**
     * @return \Generator<TaggedBlob>
     */
    public function findBlobsByTag(string $tagFilterSqlExpression): \Generator
    {
        $nextMarker = "";

        while (true) {
            $response = $this->client->get($this->uri, [
                'query' => [
                    'comp' => 'blobs',
                    'where' => $tagFilterSqlExpression,
                    'marker' => $nextMarker !== "" ? $nextMarker : null,
                ],
            ]);

            $body = FindBlobsByTagBody::fromXml(new \SimpleXMLElement($response->getBody()->getContents()));
            $nextMarker = $body->nextMarker;

            foreach ($body->blobs as $blob) {
                yield $blob;
            }

            if ($nextMarker === "") {
                break;
            }
        }
    }

    public function canGenerateAccountSasUri(): bool
    {
        return $this->credentials instanceof StorageSharedKeyCredential;
    }

    public function generateAccountSasUri(AccountSasBuilder $accountSasBuilder): UriInterface
    {
        if (!($this->credentials instanceof StorageSharedKeyCredential)) {
            throw new UnableToGenerateSasException('SAS generation requires StorageSharedKeyCredential');
        }

        if (BlobUriParserHelper::isDevelopmentUri($this->uri)) {
            $accountSasBuilder->setProtocol(SasProtocol::HTTPS_AND_HTTP);
        }

        $sas = $accountSasBuilder
            ->setServices(new AccountSasServices(blob: true))
            ->build($this->credentials);

        return new Uri("$this->uri?$sas");
    }

    /**
     * Get the shared key credentials (legacy compatibility)
     * @deprecated Use $credentials property instead
     */
    public function getSharedKeyCredentials(): ?StorageSharedKeyCredential
    {
        return $this->credentials instanceof StorageSharedKeyCredential ? $this->credentials : null;
    }

    /**
     * Legacy property access for backward compatibility
     * @deprecated Use $credentials property instead
     */
    public function __get(string $name)
    {
        if ($name === 'sharedKeyCredentials') {
            return $this->getSharedKeyCredentials();
        }
        
        throw new \InvalidArgumentException("Property $name does not exist");
    }
}
