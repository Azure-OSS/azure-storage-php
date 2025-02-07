<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob;

use AzureOss\Storage\Blob\Exceptions\BlobNotFoundException;
use AzureOss\Storage\Blob\Exceptions\BlobStorageExceptionFactory;
use AzureOss\Storage\Blob\Exceptions\InvalidBlobUriException;
use AzureOss\Storage\Blob\Exceptions\UnableToGenerateSasException;
use AzureOss\Storage\Blob\Helpers\BlobUriParserHelper;
use AzureOss\Storage\Blob\Helpers\MetadataHelper;
use AzureOss\Storage\Blob\Models\BlobDownloadStreamingResult;
use AzureOss\Storage\Blob\Models\BlobProperties;
use AzureOss\Storage\Blob\Models\UploadBlobOptions;
use AzureOss\Storage\Blob\Requests\BlobTagsBody;
use AzureOss\Storage\Blob\Requests\Block;
use AzureOss\Storage\Blob\Requests\BlockType;
use AzureOss\Storage\Blob\Requests\PutBlockRequestBody;
use AzureOss\Storage\Blob\Sas\BlobSasBuilder;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Common\Middleware\ClientFactory;
use AzureOss\Storage\Common\Sas\SasProtocol;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils as StreamUtils;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class BlobClient
{
    private readonly Client $client;

    private readonly BlobStorageExceptionFactory $exceptionFactory;

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
        $this->client = (new ClientFactory())->create($uri, $sharedKeyCredentials);
        $this->exceptionFactory = new BlobStorageExceptionFactory();
    }

    public function downloadStreaming(): BlobDownloadStreamingResult
    {
        try {
            $response = $this->client->get($this->uri, [
                'stream' => true,
            ]);

            return new BlobDownloadStreamingResult(
                $response->getBody(),
                BlobProperties::fromResponseHeaders($response),
            );
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    public function getProperties(): BlobProperties
    {
        try {
            $response = $this->client->head($this->uri);

            return BlobProperties::fromResponseHeaders($response);
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    /**
     * @param array<string> $metadata
     * @return void
     */
    public function setMetadata(array $metadata): void
    {
        try {
            $this->client->put($this->uri, [
                'query' => [
                    'comp' => 'metadata',
                ],
                'headers' => MetadataHelper::metadataToHeaders($metadata),
            ]);
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    public function delete(): void
    {
        try {
            $this->client->delete($this->uri);
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    public function deleteIfExists(): void
    {
        try {
            $this->delete();
        } catch (BlobNotFoundException) {
            // do nothing
        }
    }

    public function exists(): bool
    {
        try {
            $this->getProperties();

            return true;
        } catch (BlobNotFoundException) {
            return false;
        }
    }

    /**
     * @param string|resource|StreamInterface $content
     */
    public function upload($content, ?UploadBlobOptions $options = null): void
    {
        if ($options === null) {
            $options = new UploadBlobOptions();
        }

        $content = $this->createUploadStream($content, $options);

        if ($content->getSize() === null || ! $content->isSeekable()) {
            $this->uploadInSequentialBlocks($content, $options);
        } elseif ($content->getSize() > $options->initialTransferSize) {
            $this->uploadInParallelBlocks($content, $options);
        } else {
            $this->uploadSingle($content, $options);
        }
    }

    /**
     * @param string|resource|StreamInterface $content
     */
    private function createUploadStream($content, UploadBlobOptions $options): StreamInterface
    {
        if ($content instanceof StreamInterface) {
            $content = $content->detach();
        }

        // fix network streams only reading 8KB chunks
        if (is_resource($content)) {
            stream_set_chunk_size($content, $options->maximumTransferSize);
        }

        return StreamUtils::streamFor($content);
    }

    private function uploadSingle(StreamInterface $content, UploadBlobOptions $options): void
    {
        try {
            $this->client->put($this->uri, [
                'headers' => [
                    'x-ms-blob-type' => 'BlockBlob',
                    'Content-Type' => $options->contentType,
                    'Content-Length' => $content->getSize(),
                ],
                'body' => $content,
            ]);
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    private function uploadInSequentialBlocks(StreamInterface $content, UploadBlobOptions $options): void
    {
        $blocks = [];

        $contextMD5 = hash_init('md5');

        while (true) {
            $blockContent = $content->read($options->maximumTransferSize);

            if ($blockContent === "") {
                break;
            }

            $block = new Block(count($blocks), BlockType::UNCOMMITTED);
            $blocks[] = $block;

            hash_update($contextMD5, $blockContent);

            $this->putBlockAsync($block, $blockContent)->wait();
        }

        $contentMD5 = hash_final($contextMD5, true);

        $this->putBlockList(
            $blocks,
            $options->contentType,
            $contentMD5,
        );
    }

    private function uploadInParallelBlocks(StreamInterface $content, UploadBlobOptions $options): void
    {
        $blocks = [];

        $putBlockRequestGenerator = function () use ($content, $options, &$blocks): \Generator {
            while (true) {
                $blockContent = StreamUtils::streamFor();
                StreamUtils::copyToStream($content, $blockContent, $options->maximumTransferSize);

                if ($blockContent->getSize() === 0) {
                    break;
                }

                $block = new Block(count($blocks), BlockType::UNCOMMITTED);
                $blocks[] = $block;

                yield fn() => $this->putBlockAsync($block, $blockContent);
            }
        };

        $pool = new Pool($this->client, $putBlockRequestGenerator(), [
            'concurrency' => $options->maximumConcurrency,
            'rejected' => function (\Exception $e) {
                throw $this->exceptionFactory->create($e);
            },
        ]);

        $pool->promise()->wait();

        $this->putBlockList(
            $blocks,
            $options->contentType,
            StreamUtils::hash($content, 'md5', true),
        );
    }

    private function putBlockAsync(Block $block, StreamInterface|string $content): PromiseInterface
    {
        return $this->client
            ->putAsync($this->uri, [
                'query' => [
                    'comp' => 'block',
                    'blockid' => $block->getId(),
                ],
                'headers' => [
                    'Content-Length' => is_string($content) ? strlen($content) : $content->getSize(),
                ],
                'body' => $content,
            ]);
    }

    /**
     * @param Block[] $blocks
     */
    private function putBlockList(array $blocks, ?string $contentType, string $contentMD5): void
    {
        try {
            $this->client->put($this->uri, [
                'query' => [
                    'comp' => 'blocklist',
                ],
                'headers' => [
                    'x-ms-blob-content-type' => $contentType,
                    'x-ms-blob-content-md5' => base64_encode($contentMD5),
                ],
                'body' => (new PutBlockRequestBody($blocks))->toXml()->asXML(),
            ]);
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    public function copyFromUri(UriInterface $source): void
    {
        try {
            $this->client->put($this->uri, [
                'headers' => [
                    'x-ms-copy-source' => (string) $source,
                ],
            ]);
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
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
     * @return void
     */
    public function setTags(array $tags): void
    {
        try {
            $this->client->put($this->uri, [
                'query' => [
                    'comp' => 'tags',
                ],
                'body' => (new BlobTagsBody($tags))->toXml()->asXML(),
            ]);
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    /**
     * @return array<string>
     */
    public function getTags(): array
    {
        try {
            $response = $this->client->get($this->uri, [
                'query' => [
                    'comp' => 'tags',
                ],
            ]);

            $body = BlobTagsBody::fromXml(new \SimpleXMLElement($response->getBody()->getContents()));
            return $body->tags;
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }
}
