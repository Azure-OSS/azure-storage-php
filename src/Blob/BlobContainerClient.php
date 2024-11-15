<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob;

use AzureOss\Storage\Blob\Exceptions\BlobStorageExceptionFactory;
use AzureOss\Storage\Blob\Exceptions\ContainerAlreadyExistsException;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Blob\Exceptions\InvalidBlobUriException;
use AzureOss\Storage\Blob\Exceptions\UnableToGenerateSasException;
use AzureOss\Storage\Blob\Helpers\BlobUriParserHelper;
use AzureOss\Storage\Blob\Helpers\MetadataHelper;
use AzureOss\Storage\Blob\Models\Blob;
use AzureOss\Storage\Blob\Models\BlobContainerProperties;
use AzureOss\Storage\Blob\Models\BlobPrefix;
use AzureOss\Storage\Blob\Models\GetBlobsOptions;
use AzureOss\Storage\Blob\Models\TaggedBlob;
use AzureOss\Storage\Blob\Responses\FindBlobsByTagBody;
use AzureOss\Storage\Blob\Responses\ListBlobsResponseBody;
use AzureOss\Storage\Blob\Sas\BlobSasBuilder;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Common\Middleware\ClientFactory;
use AzureOss\Storage\Common\Sas\SasProtocol;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

final class BlobContainerClient
{
    private readonly Client $client;

    private readonly BlobStorageExceptionFactory $exceptionFactory;

    public readonly string $containerName;

    /**
     * @throws InvalidBlobUriException
     */
    public function __construct(
        public readonly UriInterface $uri,
        public readonly ?StorageSharedKeyCredential $sharedKeyCredentials = null,
    ) {
        $this->containerName = BlobUriParserHelper::getContainerName($uri);
        $this->client = (new ClientFactory())->create($uri, $sharedKeyCredentials);
        $this->exceptionFactory = new BlobStorageExceptionFactory();
    }

    public function getBlobClient(string $blobName): BlobClient
    {
        return new BlobClient(
            $this->uri->withPath($this->uri->getPath() . "/" . ltrim($blobName, "/")),
            $this->sharedKeyCredentials,
        );
    }

    public function create(): void
    {
        try {
            $this->client->put($this->uri, [
                'query' => [
                    'restype' => 'container',
                ],
            ]);
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    public function createIfNotExists(): void
    {
        try {
            $this->create();
        } catch (ContainerAlreadyExistsException) {
            // do nothing
        }
    }

    public function delete(): void
    {
        try {
            $this->client->delete($this->uri, [
                'query' => [
                    'restype' => 'container',
                ],
            ]);
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    public function deleteIfExists(): void
    {
        try {
            $this->delete();
        } catch (ContainerNotFoundException $e) {
            // do nothing
        }
    }

    public function exists(): bool
    {
        try {
            $this->client->head($this->uri, [
                'query' => [
                    'restype' => 'container',
                ],
            ]);

            return true;
        } catch (RequestException $e) {
            $e = $this->exceptionFactory->create($e);

            if ($e instanceof ContainerNotFoundException) {
                return false;
            }

            throw $e;
        }
    }

    public function getProperties(): BlobContainerProperties
    {
        try {
            $response = $this->client->get($this->uri, [
                'query' => [
                    'restype' => 'container',
                ],
            ]);

            return BlobContainerProperties::fromResponseHeaders($response);
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    /**
     * @param array<string> $metadata
     */
    public function setMetadata(array $metadata): void
    {
        try {
            $this->client->put($this->uri, [
                'query' => [
                    'restype' => 'container',
                    'comp' => 'metadata',
                ],
                'headers' => MetadataHelper::metadataToHeaders($metadata),
            ]);
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    /**
     * @return \Generator<Blob>
     */
    public function getBlobs(?string $prefix = null, ?GetBlobsOptions $options = null): \Generator
    {
        $nextMarker = "";

        while (true) {
            $response = $this->listBlobs($prefix, null, $nextMarker, $options?->pageSize);
            $nextMarker = $response->nextMarker;

            foreach ($response->blobs as $blob) {
                yield $blob;
            }

            if ($nextMarker === "") {
                break;
            }
        }
    }

    /**
     * @param string $delimiter
     * @return \Generator<Blob|BlobPrefix>
     */
    public function getBlobsByHierarchy(?string $prefix = null, string $delimiter = "/", ?GetBlobsOptions $options = null): \Generator
    {
        $nextMarker = "";

        while (true) {
            $response = $this->listBlobs($prefix, $delimiter, $nextMarker, $options?->pageSize);
            $nextMarker = $response->nextMarker;

            foreach ($response->blobs as $blob) {
                yield $blob;
            }

            foreach ($response->blobPrefixes as $blobPrefix) {
                yield $blobPrefix;
            }

            if ($nextMarker === "") {
                break;
            }
        }
    }

    private function listBlobs(?string $prefix, ?string $delimiter, string $marker, ?int $maxResults): ListBlobsResponseBody
    {
        try {
            $response = $this->client->get($this->uri, [
                'query' => [
                    'restype' => 'container',
                    'comp' => 'list',
                    'prefix' => $prefix,
                    'marker' => $marker !== "" ? $marker : null,
                    'delimiter' => $delimiter,
                    'maxresults' => $maxResults,
                ],
            ]);

            return ListBlobsResponseBody::fromXml(new \SimpleXMLElement($response->getBody()->getContents()));
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    public function canGenerateSasUri(): bool
    {
        return $this->sharedKeyCredentials !== null;
    }

    public function generateSasUri(BlobSasBuilder $blobSasBuilder): UriInterface
    {
        if ($this->sharedKeyCredentials === null) {
            throw new UnableToGenerateSasException();
        }

        if (BlobUriParserHelper::isDevelopmentUri($this->uri)) {
            $blobSasBuilder->setProtocol(SasProtocol::HTTPS_AND_HTTP);
        }

        $sas = $blobSasBuilder
            ->setContainerName($this->containerName)
            ->build($this->sharedKeyCredentials);

        return new Uri("$this->uri?$sas");
    }

    /**
     * @codeCoverageIgnore
     * @return \Generator<TaggedBlob>
     */
    public function findBlobsByTag(string $tagFilterSqlExpression): \Generator
    {
        try {
            $nextMarker = "";

            while (true) {
                $response = $this->client->get($this->uri, [
                    'query' => [
                        'restype' => 'container',
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
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }
}
