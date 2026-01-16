<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob;

use AzureOss\Storage\Blob\Exceptions\BlobStorageExceptionDeserializer;
use AzureOss\Storage\Blob\Exceptions\ContainerAlreadyExistsException;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Blob\Exceptions\InvalidBlobUriException;
use AzureOss\Storage\Blob\Exceptions\UnableToGenerateSasException;
use AzureOss\Storage\Blob\Helpers\BlobUriParserHelper;
use AzureOss\Storage\Blob\Helpers\MetadataHelper;
use AzureOss\Storage\Blob\Models\Blob;
use AzureOss\Storage\Blob\Models\BlobContainerProperties;
use AzureOss\Storage\Blob\Models\BlobPrefix;
use AzureOss\Storage\Blob\Models\CreateContainerOptions;
use AzureOss\Storage\Blob\Models\GetBlobsOptions;
use AzureOss\Storage\Blob\Models\PublicAccessType;
use AzureOss\Storage\Blob\Models\TaggedBlob;
use AzureOss\Storage\Blob\Options\BlobClientOptions;
use AzureOss\Storage\Blob\Options\BlobContainerClientOptions;
use AzureOss\Storage\Blob\Responses\FindBlobsByTagBody;
use AzureOss\Storage\Blob\Responses\ListBlobsResponseBody;
use AzureOss\Storage\Blob\Sas\BlobSasBuilder;
use AzureOss\Storage\Blob\Specialized\BlockBlobClient;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Common\Auth\TokenCredential;
use AzureOss\Storage\Common\Middleware\ClientFactory;
use AzureOss\Storage\Common\Sas\SasProtocol;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\UriInterface;

final class BlobContainerClient
{
    public const ROOT_BLOB_CONTAINER_NAME = '$root';

    public const LOGS_BLOB_CONTAINER_NAME = '$logs';

    public const WEB_BLOB_CONTAINER_NAME = '$web';

    private readonly Client $client;

    public readonly string $containerName;

    /**
     * @deprecated Use $credential instead.
     */
    public ?StorageSharedKeyCredential $sharedKeyCredentials = null;

    /**
     * @throws InvalidBlobUriException
     */
    public function __construct(
        public readonly UriInterface $uri,
        public readonly StorageSharedKeyCredential|TokenCredential|null $credential = null,
        private readonly BlobContainerClientOptions $options = new BlobContainerClientOptions,
    ) {
        $this->containerName = BlobUriParserHelper::getContainerName($uri);
        $this->client = (new ClientFactory)->create($uri, $credential, new BlobStorageExceptionDeserializer, $this->options->httpClientOptions);

        if ($credential instanceof StorageSharedKeyCredential) {
            /** @phpstan-ignore-next-line  */
            $this->sharedKeyCredentials = $credential;
        }
    }

    public function getBlobClient(string $blobName): BlobClient
    {
        return new BlobClient(
            $this->getBlobUri($blobName),
            $this->credential,
            new BlobClientOptions($this->options->httpClientOptions),
        );
    }

    public function getBlockBlobClient(string $blobName): BlockBlobClient
    {
        return new BlockBlobClient(
            $this->getBlobUri($blobName),
            $this->credential,
        );
    }

    private function getBlobUri(string $blobName): UriInterface
    {
        return $this->uri->withPath($this->uri->getPath().'/'.ltrim($blobName, '/'));
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/create-container
     */
    public function create(?CreateContainerOptions $options = null): void
    {
        $this->createAsync($options)->wait();
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/create-container
     */
    public function createAsync(?CreateContainerOptions $options = null): PromiseInterface
    {
        if ($options === null) {
            $options = new CreateContainerOptions;
        }

        $headers = [];
        if ($options->publicAccessType !== PublicAccessType::NONE) {
            $headers['x-ms-blob-public-access'] = $options->publicAccessType->value;
        }

        return $this->client->putAsync($this->uri, [
            RequestOptions::QUERY => [
                'restype' => 'container',
            ],
            RequestOptions::HEADERS => $headers,
        ]);
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/create-container
     */
    public function createIfNotExists(?CreateContainerOptions $options = null): void
    {
        $this->createIfNotExistsAsync($options)->wait();
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/create-container
     */
    public function createIfNotExistsAsync(?CreateContainerOptions $options = null): PromiseInterface
    {
        return $this->createAsync($options)
            ->otherwise(function (\Throwable $e) {
                if ($e instanceof ContainerAlreadyExistsException) {
                    return;
                }

                throw $e;
            });
    }

    public function delete(): void
    {
        $this->deleteAsync()->wait();
    }

    public function deleteAsync(): PromiseInterface
    {
        return $this->client->deleteAsync($this->uri, [
            RequestOptions::QUERY => [
                'restype' => 'container',
            ],
        ]);
    }

    public function deleteIfExists(): void
    {
        $this->deleteIfExistsAsync()->wait();
    }

    public function deleteIfExistsAsync(): PromiseInterface
    {
        return $this->deleteAsync()
            ->otherwise(function (\Throwable $e) {
                if ($e instanceof ContainerNotFoundException) {
                    return;
                }

                throw $e;
            });
    }

    public function exists(): bool
    {
        /** @phpstan-ignore-next-line */
        return $this->existsAsync()->wait();
    }

    public function existsAsync(): PromiseInterface
    {
        return $this->client
            ->headAsync($this->uri, [
                RequestOptions::QUERY => [
                    'restype' => 'container',
                ],
            ])
            ->then(fn () => true)
            ->otherwise(function (\Throwable $e) {
                if ($e instanceof ContainerNotFoundException) {
                    return false;
                }

                throw $e;
            });
    }

    public function getProperties(): BlobContainerProperties
    {
        /** @phpstan-ignore-next-line */
        return $this->getPropertiesAsync()->wait();
    }

    public function getPropertiesAsync(): PromiseInterface
    {
        return $this->client
            ->getAsync($this->uri, [
                RequestOptions::QUERY => [
                    'restype' => 'container',
                ],
            ])
            ->then(BlobContainerProperties::fromResponseHeaders(...));
    }

    /**
     * @param  array<string>  $metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->setMetadataAsync($metadata)->wait();
    }

    /**
     * @param  array<string>  $metadata
     */
    public function setMetadataAsync(array $metadata): PromiseInterface
    {
        return $this->client->putAsync($this->uri, [
            RequestOptions::QUERY => [
                'restype' => 'container',
                'comp' => 'metadata',
            ],
            RequestOptions::HEADERS => MetadataHelper::metadataToHeaders($metadata),
        ]);
    }

    /**
     * @return \Generator<Blob>
     */
    public function getBlobs(?string $prefix = null, ?GetBlobsOptions $options = null): \Generator
    {
        $nextMarker = '';

        while (true) {
            $response = $this->listBlobs($prefix, null, $nextMarker, $options?->pageSize);
            $nextMarker = $response->nextMarker;

            foreach ($response->blobs as $blob) {
                yield $blob;
            }

            if ($nextMarker === '') {
                break;
            }
        }
    }

    /**
     * @return \Generator<Blob|BlobPrefix>
     */
    public function getBlobsByHierarchy(?string $prefix = null, string $delimiter = '/', ?GetBlobsOptions $options = null): \Generator
    {
        $nextMarker = '';

        while (true) {
            $response = $this->listBlobs($prefix, $delimiter, $nextMarker, $options?->pageSize);
            $nextMarker = $response->nextMarker;

            foreach ($response->blobs as $blob) {
                yield $blob;
            }

            foreach ($response->blobPrefixes as $blobPrefix) {
                yield $blobPrefix;
            }

            if ($nextMarker === '') {
                break;
            }
        }
    }

    private function listBlobs(?string $prefix, ?string $delimiter, string $marker, ?int $maxResults): ListBlobsResponseBody
    {
        $response = $this->client->get($this->uri, [
            RequestOptions::QUERY => [
                'restype' => 'container',
                'comp' => 'list',
                'prefix' => $prefix,
                'marker' => $marker !== '' ? $marker : null,
                'delimiter' => $delimiter,
                'maxresults' => $maxResults,
            ],
        ]);

        return ListBlobsResponseBody::fromXml(new \SimpleXMLElement($response->getBody()->getContents()));
    }

    public function canGenerateSasUri(): bool
    {
        return $this->credential instanceof StorageSharedKeyCredential;
    }

    public function generateSasUri(BlobSasBuilder $blobSasBuilder): UriInterface
    {
        if (! $this->credential instanceof StorageSharedKeyCredential) {
            throw new UnableToGenerateSasException;
        }

        if (BlobUriParserHelper::isDevelopmentUri($this->uri)) {
            $blobSasBuilder->setProtocol(SasProtocol::HTTPS_AND_HTTP);
        }

        $sas = $blobSasBuilder
            ->setContainerName($this->containerName)
            ->build($this->credential);

        return new Uri("$this->uri?$sas");
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
                    'restype' => 'container',
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
}
