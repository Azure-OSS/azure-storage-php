<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob;

use AzureOss\Storage\Blob\Exceptions\BlobNotFoundException;
use AzureOss\Storage\Blob\Exceptions\BlobStorageExceptionDeserializer;
use AzureOss\Storage\Blob\Exceptions\InvalidBlobUriException;
use AzureOss\Storage\Blob\Exceptions\UnableToGenerateSasException;
use AzureOss\Storage\Blob\Helpers\BlobUriParserHelper;
use AzureOss\Storage\Blob\Helpers\MetadataHelper;
use AzureOss\Storage\Blob\Helpers\StreamHelper;
use AzureOss\Storage\Blob\Models\BlobDownloadStreamingResult;
use AzureOss\Storage\Blob\Models\BlobProperties;
use AzureOss\Storage\Blob\Models\BlockBlobCommitBlockListOptions;
use AzureOss\Storage\Blob\Models\UploadBlobOptions;
use AzureOss\Storage\Blob\Requests\BlobTagsBody;
use AzureOss\Storage\Blob\Sas\BlobSasBuilder;
use AzureOss\Storage\Blob\Specialized\BlockBlobClient;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Common\Middleware\ClientFactory;
use AzureOss\Storage\Common\Sas\SasProtocol;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils as StreamUtils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class BlobClient
{
    private readonly Client $client;

    private readonly BlockBlobClient $blockBlobClient;

    public readonly string $containerName;

    public readonly string $blobName;

    /**
     * @throws InvalidBlobUriException
     */
    public function __construct(
        public readonly UriInterface $uri,
        public readonly ?StorageSharedKeyCredential $sharedKeyCredentials = null,
    ) {
        $this->containerName = BlobUriParserHelper::getContainerName($uri);
        $this->blobName = BlobUriParserHelper::getBlobName($uri);
        $this->client = (new ClientFactory())->create($uri, $sharedKeyCredentials, new BlobStorageExceptionDeserializer());
        $this->blockBlobClient = new BlockBlobClient($uri, $sharedKeyCredentials);
    }

    public function downloadStreaming(): BlobDownloadStreamingResult
    {
        /** @phpstan-ignore-next-line */
        return $this->downloadStreamingAsync()->wait();
    }

    public function downloadStreamingAsync(): PromiseInterface
    {
        return $this->client
            ->getAsync($this->uri, [
                'stream' => true,
            ])
            ->then(BlobDownloadStreamingResult::fromResponse(...));
    }

    public function getProperties(): BlobProperties
    {
        /** @phpstan-ignore-next-line */
        return $this->getPropertiesAsync()->wait();
    }

    public function getPropertiesAsync(): PromiseInterface
    {
        return $this->client
            ->headAsync($this->uri)
            ->then(BlobProperties::fromResponseHeaders(...));
    }

    /**
     * @param array<string> $metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->setMetadataAsync($metadata)->wait();
    }

    /**
     * @param array<string> $metadata
     */
    public function setMetadataAsync(array $metadata): PromiseInterface
    {
        return $this->client
            ->putAsync($this->uri, [
                'query' => [
                    'comp' => 'metadata',
                ],
                'headers' => MetadataHelper::metadataToHeaders($metadata),
            ]);
    }

    public function delete(): void
    {
        $this->deleteAsync()->wait();
    }

    public function deleteAsync(): PromiseInterface
    {
        return $this->client->deleteAsync($this->uri);
    }

    public function deleteIfExists(): void
    {
        $this->deleteIfExistsAsync()->wait();
    }

    public function deleteIfExistsAsync(): PromiseInterface
    {
        return $this->deleteAsync()->otherwise(
            function (\Throwable $e) {
                if ($e instanceof BlobNotFoundException) {
                    return null;
                }

                throw $e;
            },
        );
    }

    public function exists(): bool
    {
        /** @phpstan-ignore-next-line */
        return $this->existsAsync()->wait();
    }

    public function existsAsync(): PromiseInterface
    {
        return $this->getPropertiesAsync()
            ->then(fn() => true)
            ->otherwise(
                function (\Throwable $e) {
                    if ($e instanceof BlobNotFoundException) {
                        return false;
                    }

                    throw $e;
                },
            );
    }

    /**
     * @param string|resource|StreamInterface $content
     */
    public function upload($content, ?UploadBlobOptions $options = null): void
    {
        $this->uploadAsync($content, $options)->wait();
    }

    /**
     * @param string|resource|StreamInterface $content
     */
    public function uploadAsync($content, ?UploadBlobOptions $options = null): PromiseInterface
    {
        if ($options === null) {
            $options = new UploadBlobOptions();
        }

        $content = StreamHelper::createUploadStream($content, $options->maximumTransferSize);

        if ($content->getSize() === null || ! $content->isSeekable()) {
            return $this->uploadInSequentialBlocksAsync($content, $options);
        } elseif ($content->getSize() > $options->initialTransferSize) {
            return $this->uploadInParallelBlocksAsync($content, $options);
        } else {
            return $this->uploadSingleAsync($content, $options);
        }
    }

    private function uploadSingleAsync(StreamInterface $content, UploadBlobOptions $options): PromiseInterface
    {
        return $this->client
            ->putAsync($this->uri, [
                'headers' => [
                    'x-ms-blob-type' => 'BlockBlob',
                    'Content-Type' => $options->contentType,
                    'Content-Length' => $content->getSize(),
                ],
                'body' => $content,
            ]);
    }

    private function uploadInSequentialBlocksAsync(StreamInterface $content, UploadBlobOptions $options): PromiseInterface
    {
        $blockIds = [];
        $contextMD5 = hash_init('md5');

        $putBlockRequestGenerator = function () use (&$content, &$options, &$blockIds, &$contextMD5): \Generator {
            while (true) {
                $blockContent = $content->read($options->maximumTransferSize);
                if ($blockContent === "") {
                    break;
                }

                $blockId = $this->getNextBlockId($blockIds);
                $blockIds[] = $blockId;

                hash_update($contextMD5, $blockContent);

                yield fn() => $this->blockBlobClient->stageBlockAsync($blockId, $blockContent);
            }
        };

        return (new Pool(
            $this->client,
            $putBlockRequestGenerator(),
            ['concurrency' => 1],
        ))
            ->promise()
            ->then(
                function () use (&$blockIds, &$options, &$contextMD5) {
                    return $this->blockBlobClient->commitBlockListAsync(
                        $blockIds,
                        new BlockBlobCommitBlockListOptions(
                            contentType: $options->contentType,
                            contentMD5: hash_final($contextMD5, true),
                        ),
                    );
                },
            );
    }

    private function uploadInParallelBlocksAsync(StreamInterface $content, UploadBlobOptions $options): PromiseInterface
    {
        $blockIds = [];

        $putBlockRequestGenerator = function () use (&$content, &$options, &$blockIds): \Generator {
            while (true) {
                $blockContent = StreamUtils::streamFor();
                StreamUtils::copyToStream($content, $blockContent, $options->maximumTransferSize);
                if ($blockContent->getSize() === 0) {
                    break;
                }

                $blockId = $this->getNextBlockId($blockIds);
                $blockIds[] = $blockId;

                yield fn() => $this->blockBlobClient->stageBlock($blockId, $blockContent);
            }
        };

        $pool = new Pool(
            $this->client,
            $putBlockRequestGenerator(),
            ['concurrency' => $options->maximumConcurrency],
        );

        return $pool
            ->promise()
            ->then(
                function () use (&$content, &$blockIds, &$options) {
                    return $this->blockBlobClient->commitBlockListAsync(
                        $blockIds,
                        new BlockBlobCommitBlockListOptions(
                            contentType: $options->contentType,
                            contentMD5: StreamUtils::hash($content, 'md5', true),
                        ),
                    );
                },
            );
    }

    /**
     * @param string[] $blockIds
     */
    private function getNextBlockId(array $blockIds): string
    {
        return base64_encode(str_pad((string) count($blockIds), 6, '0', STR_PAD_LEFT));
    }

    public function copyFromUri(UriInterface $source): void
    {
        $this->copyFromUriAsync($source)->wait();
    }

    public function copyFromUriAsync(UriInterface $source): PromiseInterface
    {
        return $this->client
            ->putAsync($this->uri, [
                'headers' => [
                    'x-ms-copy-source' => (string) $source,
                ],
            ]);
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
            ->setBlobName($this->blobName)
            ->build($this->sharedKeyCredentials);

        return new Uri("$this->uri?$sas");
    }

    /**
     * @param array<string> $tags
     */
    public function setTags(array $tags): void
    {
        $this->setTagsAsync($tags)->wait();
    }

    /**
     * @param array<string> $tags
     */
    public function setTagsAsync(array $tags): PromiseInterface
    {
        return $this->client
            ->putAsync($this->uri, [
                'query' => [
                    'comp' => 'tags',
                ],
                'body' => (new BlobTagsBody($tags))->toXml()->asXML(),
            ]);
    }

    /**
     * @return array<string>
     */
    public function getTags(): array
    {
        /** @phpstan-ignore-next-line */
        return $this->getTagsAsync()->wait();
    }

    public function getTagsAsync(): PromiseInterface
    {
        return $this->client
            ->getAsync($this->uri, [
                'query' => [
                    'comp' => 'tags',
                ],
            ])
            ->then(
                fn(ResponseInterface $response) => BlobTagsBody::fromXml(new \SimpleXMLElement($response->getBody()->getContents()))->tags,
            );
    }
}
