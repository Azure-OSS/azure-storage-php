<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob;

use AzureOss\Storage\Blob\Exceptions\BlobNotFoundExceptionBlob;
use AzureOss\Storage\Blob\Exceptions\BlobStorageExceptionFactory;
use AzureOss\Storage\Blob\Exceptions\InvalidBlobUriException;
use AzureOss\Storage\Blob\Exceptions\UnableToGenerateSasException;
use AzureOss\Storage\Blob\Exceptions\UnableToUploadBlobException;
use AzureOss\Storage\Blob\Models\BlobDownloadStreamingResult;
use AzureOss\Storage\Blob\Models\BlobProperties;
use AzureOss\Storage\Blob\Models\UploadBlobOptions;
use AzureOss\Storage\Blob\Requests\Block;
use AzureOss\Storage\Blob\Requests\BlockType;
use AzureOss\Storage\Blob\Requests\PutBlockRequestBody;
use AzureOss\Storage\Blob\Sas\BlobSasBuilder;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Common\Middleware\ClientFactory;
use AzureOss\Storage\Common\Sas\SasProtocol;
use AzureOss\Storage\Common\Serializer\SerializerFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils as StreamUtils;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class BlobClient
{
    private readonly Client $client;

    private readonly BlobStorageExceptionFactory $exceptionFactory;

    private readonly SerializerInterface $serializer;

    public readonly string $containerName;

    public readonly string $blobName;

    /**
     * @throws InvalidBlobUriException
     */
    public function __construct(
        public readonly UriInterface $uri,
        public readonly ?StorageSharedKeyCredential $sharedKeyCredentials = null,
    ) {
        $this->containerName = BlobUriParser::getContainerName($uri);
        $this->blobName = BlobUriParser::getBlobName($uri);
        $this->client = (new ClientFactory())->create($sharedKeyCredentials);
        $this->serializer = (new SerializerFactory())->create();
        $this->exceptionFactory = new BlobStorageExceptionFactory($this->serializer);
    }

    /**
     * @param array<string, string|null> $query
     * @return array<string, string>
     */
    private function buildQuery(array $query): array
    {
        return array_filter([
            ...Query::parse($this->uri->getQuery()),
            ...$query,
        ]);
    }

    public function downloadStreaming(): BlobDownloadStreamingResult
    {
        try {
            $response = $this->client->get($this->uri, [
                'stream' => true,
            ]);

            return new BlobDownloadStreamingResult(
                $response->getBody(),
                new BlobProperties(
                    new \DateTime($response->getHeaderLine('Last-Modified')),
                    (int) $response->getHeaderLine('Content-Length'),
                    $response->getHeaderLine('Content-Type'),
                ),
            );
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    public function getProperties(): BlobProperties
    {
        try {
            $response = $this->client->head($this->uri);

            return new BlobProperties(
                new \DateTime($response->getHeaderLine('Last-Modified')),
                (int) $response->getHeaderLine('Content-Length'),
                $response->getHeaderLine('Content-Type'),
            );
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
        } catch (BlobNotFoundExceptionBlob) {
            // do nothing
        }
    }

    public function exists(): bool
    {
        try {
            $this->getProperties();

            return true;
        } catch (BlobNotFoundExceptionBlob) {
            return false;
        }
    }

    /**
     * @param string|resource|StreamInterface $content
     */
    public function upload($content, ?UploadBlobOptions $options = null): void
    {
        if($options === null) {
            $options = new UploadBlobOptions();
        }

        $content = StreamUtils::streamFor($content);
        $contentLength = $content->getSize();

        if ($contentLength === null) {
            throw new UnableToUploadBlobException();
        }

        if ($contentLength <= $options->initialTransferSize) {
            $this->uploadSingle($content, $options);
        } else {
            $this->uploadInBlocks($content, $options);
        }
    }

    /**
     * @param string|resource|StreamInterface $content
     */
    private function uploadSingle($content, UploadBlobOptions $options): void
    {
        try {
            $this->client->put($this->uri, [
                'headers' => [
                    'x-ms-blob-type' => 'BlockBlob',
                    'Content-Type' => $options->contentType,
                ],
                'body' => $content,
            ]);
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    private function uploadInBlocks(StreamInterface $content, UploadBlobOptions $options): void
    {
        $blocks = [];

        $putBlockRequestGenerator = function () use ($content, $options, &$blocks): \Iterator {
            while (! $content->eof()) {
                $blockContent = StreamUtils::streamFor();
                StreamUtils::copyToStream($content, $blockContent, $options->maximumTransferSize);

                $blockId = str_pad((string) count($blocks), 6, '0', STR_PAD_LEFT);
                $block = new Block($blockId, BlockType::UNCOMMITTED);
                $blocks[] = $block;

                yield fn() => $this->putBlockAsync($block, $blockContent);
            }
        };

        $pool = new Pool($this->client, $putBlockRequestGenerator(), [
            'concurrency' => $options->maximumConcurrency,
            'rejected' => function (RequestException $e) {
                throw $this->exceptionFactory->create($e);
            },
        ]);

        $pool->promise()->wait();

        $this->putBlockList($blocks, $options);
    }

    private function putBlockAsync(Block $block, StreamInterface $content): PromiseInterface
    {
        return $this->client
            ->putAsync($this->uri, [
                'query' => $this->buildQuery([
                    'comp' => 'block',
                    'blockid' => base64_encode($block->id),
                ]),
                'body' => $content,
            ]);
    }

    /**
     * @param Block[] $blocks
     */
    private function putBlockList(array $blocks, UploadBlobOptions $options): void
    {
        try {
            $this->client->put($this->uri, [
                'query' => $this->buildQuery([
                    'comp' => 'blocklist',
                ]),
                'headers' => [
                    'x-ms-blob-content-type' => $options->contentType,
                ],
                'body' => $this->serializer->serialize(new PutBlockRequestBody($blocks), 'xml'),
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

        if (BlobUriParser::isDevelopmentUri($this->uri)) {
            $blobSasBuilder->setProtocol(SasProtocol::HTTPS_AND_HTTP);
        }

        $sas = $blobSasBuilder
            ->setContainerName($this->containerName)
            ->setBlobName($this->blobName)
            ->build($this->sharedKeyCredentials);

        return new Uri("$this->uri?$sas");
    }
}
