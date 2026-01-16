<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob;

use AzureOss\Storage\Blob\Exceptions\BlobStorageExceptionDeserializer;
use AzureOss\Storage\Blob\Exceptions\InvalidConnectionStringException;
use AzureOss\Storage\Blob\Exceptions\UnableToGenerateSasException;
use AzureOss\Storage\Blob\Helpers\BlobUriParserHelper;
use AzureOss\Storage\Blob\Models\BlobContainer;
use AzureOss\Storage\Blob\Models\TaggedBlob;
use AzureOss\Storage\Blob\Options\BlobContainerClientOptions;
use AzureOss\Storage\Blob\Options\BlobServiceClientOptions;
use AzureOss\Storage\Blob\Responses\FindBlobsByTagBody;
use AzureOss\Storage\Blob\Responses\ListContainersResponseBody;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Common\Auth\TokenCredential;
use AzureOss\Storage\Common\Helpers\ConnectionStringHelper;
use AzureOss\Storage\Common\Middleware\ClientFactory;
use AzureOss\Storage\Common\Sas\AccountSasBuilder;
use AzureOss\Storage\Common\Sas\AccountSasServices;
use AzureOss\Storage\Common\Sas\SasProtocol;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\UriInterface;

final class BlobServiceClient
{
    private readonly Client $client;

    /**
     * @deprecated Use $credential instead.
     */
    public ?StorageSharedKeyCredential $sharedKeyCredentials = null;

    public function __construct(
        public UriInterface $uri,
        public readonly StorageSharedKeyCredential|TokenCredential|null $credential = null,
        private readonly BlobServiceClientOptions $options = new BlobServiceClientOptions,
    ) {
        // must always include the forward slash (/) to separate the host name from the path and query portions of the URI.
        $this->uri = $uri->withPath(rtrim($uri->getPath(), '/').'/');
        $this->client = (new ClientFactory)->create($this->uri, $credential, new BlobStorageExceptionDeserializer, $this->options->httpClientOptions);

        if ($credential instanceof StorageSharedKeyCredential) {
            /** @phpstan-ignore-next-line  */
            $this->sharedKeyCredentials = $credential;
        }
    }

    public static function fromConnectionString(string $connectionString, BlobServiceClientOptions $options = new BlobServiceClientOptions): self
    {
        $uri = ConnectionStringHelper::getBlobEndpoint($connectionString);
        if ($uri === null) {
            throw new InvalidConnectionStringException;
        }

        $sas = ConnectionStringHelper::getSas($connectionString);
        if ($sas !== null) {
            return new self($uri->withQuery($sas), options: $options);
        }

        $accountName = ConnectionStringHelper::getAccountName($connectionString);
        $accountKey = ConnectionStringHelper::getAccountKey($connectionString);
        if ($accountName !== null && $accountKey !== null) {
            return new self($uri, new StorageSharedKeyCredential($accountName, $accountKey), $options);
        }

        throw new InvalidConnectionStringException;
    }

    public function getContainerClient(string $containerName): BlobContainerClient
    {
        return new BlobContainerClient(
            $this->uri->withPath($this->uri->getPath().$containerName),
            $this->credential,
            new BlobContainerClientOptions($this->options->httpClientOptions),
        );
    }

    /**
     * @return \Generator<BlobContainer>
     */
    public function getBlobContainers(?string $prefix = null): \Generator
    {
        $nextMarker = '';

        while (true) {
            $response = $this->client->get($this->uri, [
                RequestOptions::QUERY => [
                    'comp' => 'list',
                    'marker' => $nextMarker !== '' ? $nextMarker : null,
                    'prefix' => $prefix,
                ],
            ]);
            $body = ListContainersResponseBody::fromXml(new \SimpleXMLElement($response->getBody()->getContents()));
            $nextMarker = $body->nextMarker;

            foreach ($body->containers as $container) {
                yield $container;
            }

            if ($nextMarker === '') {
                break;
            }
        }
    }

    /**
     * @return \Generator<TaggedBlob>
     */
    public function findBlobsByTag(string $tagFilterSqlExpression): \Generator
    {
        $nextMarker = '';

        while (true) {
            $response = $this->client->get($this->uri, [
                RequestOptions::QUERY => [
                    'comp' => 'blobs',
                    'where' => $tagFilterSqlExpression,
                    'marker' => $nextMarker !== '' ? $nextMarker : null,
                ],
            ]);

            $body = FindBlobsByTagBody::fromXml(new \SimpleXMLElement($response->getBody()->getContents()));
            $nextMarker = $body->nextMarker;

            foreach ($body->blobs as $blob) {
                yield $blob;
            }

            if ($nextMarker === '') {
                break;
            }
        }
    }

    public function canGenerateAccountSasUri(): bool
    {
        return $this->credential instanceof StorageSharedKeyCredential;
    }

    public function generateAccountSasUri(AccountSasBuilder $accountSasBuilder): UriInterface
    {
        if (! $this->credential instanceof StorageSharedKeyCredential) {
            throw new UnableToGenerateSasException;
        }

        if (BlobUriParserHelper::isDevelopmentUri($this->uri)) {
            $accountSasBuilder->setProtocol(SasProtocol::HTTPS_AND_HTTP);
        }

        $sas = $accountSasBuilder
            ->setServices(new AccountSasServices(blob: true))
            ->build($this->credential);

        return new Uri("$this->uri?$sas");
    }
}
