<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob;

use AzureOss\Storage\Blob\Options\Block;
use AzureOss\Storage\Blob\Options\BlockType;
use AzureOss\Storage\Blob\Options\PutBlobOptions;
use AzureOss\Storage\Blob\Options\PutBlockListOptions;
use AzureOss\Storage\Blob\Options\PutBlockOptions;
use AzureOss\Storage\Blob\Options\UploadBlockBlobOptions;
use AzureOss\Storage\Blob\Requests\PutBlockRequestBody;
use AzureOss\Storage\Blob\Responses\PutBlockListResponse;
use AzureOss\Storage\Blob\Responses\PutBlockResponse;
use AzureOss\Storage\Common\Auth\Credentials;
use AzureOss\Storage\Common\Exceptions\ExceptionFactory;
use AzureOss\Storage\Common\Middleware\MiddlewareFactory;
use AzureOss\Storage\Common\Serializer\SerializerFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Utils as StreamUtils;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\StreamInterface;

class BlockBlobClient
{
    public const MAX_BLOCK_SIZE = 4_000_000_000;

    public const MAX_BLOCK_COUNT = 50_000;

    private readonly Client $client;

    private HandlerStack $handlerStack;

    private readonly ExceptionFactory $exceptionFactory;

    private readonly SerializerInterface $serializer;

    /**
     * @var int[]
     */
    private array $blockSizeThresholds = [
        8_000_000, // 8MB
        100_000_000, // 100MB
        4_000_000_000, // 4GB
    ];

    private int $singleUploadThreshold = 256 * 1000 ** 2; // 256MB

    public function __construct(
        public readonly string $blobEndpoint,
        public readonly string $containerName,
        public readonly string $blobName,
        public readonly Credentials $credentials
    ) {
        $this->handlerStack = (new MiddlewareFactory())->create(BlobServiceClient::API_VERSION, $credentials);
        $this->client = new Client(['handler' => $this->handlerStack]);
        $this->exceptionFactory = new ExceptionFactory();
        $this->serializer = (new SerializerFactory())->create();
    }

    private function getUrl(): string
    {
        return $this->blobEndpoint . '/' . $this->containerName . '/' . $this->blobName;
    }

    public function getBlobClient(): BlobClient
    {
        return new BlobClient($this->blobEndpoint, $this->containerName, $this->blobName, $this->credentials);
    }

    public function getContainerClient(): ContainerClient
    {
        return new ContainerClient($this->blobEndpoint, $this->containerName, $this->credentials);
    }

    public function getHandlerStack(): HandlerStack
    {
        return $this->handlerStack;
    }

    public function setSingleUploadThreshold(int $bytes): void
    {
        $this->singleUploadThreshold = $bytes;
    }

    /**
     * @param  int[]  $thresholds
     */
    public function setBlockSizeThresholds(array $thresholds): void
    {
        foreach ($thresholds as $bytes) {
            if ($bytes > self::MAX_BLOCK_SIZE) {
                throw new \LogicException('A block can be a maximum of 4,000 mebibytes (MiB).');
            }
        }

        $this->blockSizeThresholds = $thresholds;
    }

    private function getBlockSize(int $size): int
    {
        foreach ($this->blockSizeThresholds as $chunkSize) {
            if ($size / $chunkSize <= self::MAX_BLOCK_COUNT) {
                return $chunkSize;
            }
        }

        throw new \LogicException('A block blob can include a maximum of 50,000 committed blocks.');
    }

    /**
     * @param string|resource|StreamInterface $content
     */
    public function upload($content, ?UploadBlockBlobOptions $options = null): void
    {
        $stream = StreamUtils::streamFor($content);

        if ($stream->getSize() > $this->singleUploadThreshold) {
            $this->uploadInChunks($stream, $options);
        } else {
            $this->getBlobClient()->put($content, new PutBlobOptions($options?->contentType));
        }
    }

    private function uploadInChunks(StreamInterface $content, ?UploadBlockBlobOptions $options = null): void
    {
        $blocks = [];
        $contentSize = $content->getSize();

        if ($contentSize === null) {
            throw new \Exception("Invalid stream");
        }

        $blockSize = $this->getBlockSize($contentSize);

        $putBlockRequestGenerator = function () use ($content, $blockSize, &$blocks): \Iterator {
            while (! $content->eof()) {
                $blockContent = StreamUtils::streamFor();
                StreamUtils::copyToStream($content, $blockContent, $blockSize);

                $blockId = str_pad((string) count($blocks), 6, '0', STR_PAD_LEFT);
                $block = new Block($blockId, BlockType::UNCOMMITTED);
                $blocks[] = $block;

                yield fn () => $this->putBlockAsync($block, $blockContent);
            }
        };

        $pool = new Pool($this->client, $putBlockRequestGenerator(), [
            'rejected' => function (RequestException $e) {
                throw $this->exceptionFactory->create($e);
            },
        ]);

        $pool->promise()->wait();

        $this->putBlockList($blocks, new PutBlockListOptions($options?->contentType));
    }

    /**
     * @param string|resource|StreamInterface $content
     */
    public function putBlock(Block $block, $content, ?PutBlockOptions $options = null): PutBlockResponse
    {
        try {
            /** @phpstan-ignore-next-line */
            return $this->putBlockAsync($block, StreamUtils::streamFor($content), $options)->wait();
        } catch(RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    private function putBlockAsync(Block $block, StreamInterface $content, ?PutBlockOptions $options = null): PromiseInterface
    {
        return $this->client
            ->putAsync($this->getUrl(), [
                'query' => [
                    'comp' => 'block',
                    'blockid' => base64_encode($block->id),
                ],
                'body' => $content,
            ])
            ->then(function () {
                return new PutBlockResponse();
            });
    }

    /**
     * @param Block[] $blocks
     */
    public function putBlockList(array $blocks, ?PutBlockListOptions $options = null): PutBlockListResponse
    {
        try {
            $this->client->put($this->getUrl(), [
                'query' => [
                    'comp' => 'blocklist',
                ],
                'headers' => [
                    'x-ms-blob-content-type' => $options?->contentType,
                ],
                'body' => $this->serializer->serialize(new PutBlockRequestBody($blocks), 'xml'),
            ]);

            return new PutBlockListResponse();
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }
}
