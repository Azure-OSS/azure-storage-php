<?php

declare(strict_types=1);

namespace AzureOss\Storage;

use AzureOss\Storage\Exceptions\AuthorizationFailedException;
use AzureOss\Storage\Exceptions\BlobNotFoundException;
use AzureOss\Storage\Exceptions\ContainerAlreadyExistsException;
use AzureOss\Storage\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Exceptions\InvalidBlockListException;
use AzureOss\Storage\Interfaces\AuthScheme;
use AzureOss\Storage\Interfaces\BlobClient;
use AzureOss\Storage\Middleware\AddAuthorizationHeaderMiddleware;
use AzureOss\Storage\Middleware\AddContentHeadersMiddleware;
use AzureOss\Storage\Middleware\AddXMsDateHeaderMiddleware;
use AzureOss\Storage\Middleware\AddXMsVersionMiddleware;
use AzureOss\Storage\Requests\Block;
use AzureOss\Storage\Requests\BlockType;
use AzureOss\Storage\Requests\CreateContainerOptions;
use AzureOss\Storage\Requests\DeleteBlobOptions;
use AzureOss\Storage\Requests\DeleteContainerOptions;
use AzureOss\Storage\Requests\GetBlobOptions;
use AzureOss\Storage\Requests\GetBlobPropertiesOptions;
use AzureOss\Storage\Requests\GetContainerPropertiesOptions;
use AzureOss\Storage\Requests\ListBlobsOptions;
use AzureOss\Storage\Requests\MissingOption;
use AzureOss\Storage\Requests\PutBlobOptions;
use AzureOss\Storage\Requests\PutBlockListOptions;
use AzureOss\Storage\Requests\PutBlockOptions;
use AzureOss\Storage\Requests\UploadBlockBlobOptions;
use AzureOss\Storage\Responses\CreateContainerResponse;
use AzureOss\Storage\Responses\DeleteBlobResponse;
use AzureOss\Storage\Responses\DeleteContainerResponse;
use AzureOss\Storage\Responses\ErrorCode;
use AzureOss\Storage\Responses\GetBlobPropertiesResponse;
use AzureOss\Storage\Responses\GetBlobResponse;
use AzureOss\Storage\Responses\GetContainerPropertiesResponse;
use AzureOss\Storage\Responses\ListBlobsResponse;
use AzureOss\Storage\Responses\PutBlobResponse;
use AzureOss\Storage\Responses\PutBlockListResponse;
use AzureOss\Storage\Responses\PutBlockResponse;
use AzureOss\Storage\Serializer\PascalCaseToCamelCaseConverter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Utils as StreamUtils;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

final class BlobApiClient implements BlobClient
{
    public const API_VERSION = '2024-08-04';

    private  Client $client;

    private  HandlerStack $handlerStack;

    private  SerializerInterface $serializer;

    /**
     * @var int[]
     */
    private array $parallelBlobUploadBlockSizeThresholds = [
        4_000_000, // 4MB
        100_000_000, // 100MB
        4_000_000_000, // 4GB
    ];

    private int $singleBlobUploadThreshold = 32 * 1000 ** 2; // 32 MB

    public function __construct(
         public StorageServiceSettings $settings,
         private AuthScheme $authScheme,
    ) {
        $this->serializer = $this->createSerializer();
        $this->handlerStack = $this->createHandlerStack();
        $this->client = $this->createHttpClient();
    }

    private function createHandlerStack(): HandlerStack
    {
        $handlerStack = HandlerStack::create();

        $handlerStack->push(new AddContentHeadersMiddleware());
        $handlerStack->push(new AddXMsDateHeaderMiddleware());
        $handlerStack->push(new AddXMsVersionMiddleware(self::API_VERSION));
        $handlerStack->push(new AddAuthorizationHeaderMiddleware($this->authScheme));

        return $handlerStack;
    }

    private function createHttpClient(): Client
    {
        return new Client(['handler' => $this->handlerStack]);
    }

    private function createSerializer(): SerializerInterface
    {
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $metadataAwareNameConverter = new MetadataAwareNameConverter($classMetadataFactory, new PascalCaseToCamelCaseConverter());
        $phpDocExtractor = new PhpDocExtractor();

        $normalizers = [
            new ArrayDenormalizer(),
            new ObjectNormalizer($classMetadataFactory, $metadataAwareNameConverter, null, $phpDocExtractor),
        ];

        $encoders = [
            new XmlEncoder(),
        ];

        return new Serializer($normalizers, $encoders);
    }

    private function buildBlobUri(string $container, string $blob): string
    {
        return $this->buildContainerUri($container)."/$blob";
    }

    private function buildContainerUri(string $container): string
    {
        return $this->settings->blobEndpoint."/$container";
    }

    /**
     * @param array<string, mixed|MissingOption> $query
     */
    private function buildQuery(array $query): string
    {
        $query = array_filter($query, fn ($value) => ! $value instanceof MissingOption);

        return Query::build($query);
    }

    /**
     * @param array<string, string|MissingOption> $headers
     * @return array<string, string>
     */
    private function buildHeaders(array $headers): array
    {
        return array_filter($headers, fn ($value) => ! $value instanceof MissingOption);
    }

    /**
     * @template T of mixed
     *
     * @param  class-string<T>  $className
     * @return T
     */
    private function deserializeBody(StreamInterface $body, string $className): mixed
    {
        return $this->serializer->deserialize($body->getContents(), $className, 'xml');
    }

    /**
     * @param array<string, mixed> $rootNode
     */
    private function serializeBody(string $rootNodeName, array $rootNode): string
    {
        return $this->serializer->serialize($rootNode, 'xml', [
            'xml_root_node_name' => $rootNodeName,
        ]);
    }

    public function getHandlerStack(): HandlerStack
    {
        return $this->handlerStack;
    }

    public function setSingleBlobUploadThreshold(int $bytes): void
    {
        $this->singleBlobUploadThreshold = $bytes;
    }

    /**
     * @param  int[]  $thresholds
     */
    public function setParallelBlobUploadBlobSizeThresholds(array $thresholds): void
    {
        foreach ($thresholds as $bytes) {
            if ($bytes > 4000) {
                throw new \LogicException('A block can be a maximum of 4,000 mebibytes (MiB).');
            }
        }

        $this->parallelBlobUploadBlockSizeThresholds = $thresholds;
    }

    private function getParallelBlobUploadBlockSize(int $size): int
    {
        foreach ($this->parallelBlobUploadBlockSizeThresholds as $chunkSize) {
            if ($size / $chunkSize <= 50000) {
                return $chunkSize;
            }
        }

        throw new \LogicException('A block blob can include a maximum of 50,000 committed blocks.');
    }

    public function createContainer(string $container, ?CreateContainerOptions $options = null): CreateContainerResponse
    {
        try {
            $uri = $this->buildContainerUri($container);

            $query = $this->buildQuery([
                'restype' => 'container',
            ]);

            $this->client->put($uri, [
                'query' => $query,
            ]);

            return new CreateContainerResponse();
        } catch (RequestException $e) {
            throw $this->convertRequestException($e);
        }
    }

    public function getContainerProperties(string $container, ?GetContainerPropertiesOptions $options = null): GetContainerPropertiesResponse
    {
        try {
            $uri = $this->buildContainerUri($container);

            $query = $this->buildQuery([
                'restype' => 'container',
            ]);

            $this->client->head($uri, [
                'query' => $query,
            ]);

            return new GetContainerPropertiesResponse();
        } catch (RequestException $e) {
            throw $this->convertRequestException($e);
        }
    }

    public function deleteContainer(string $container, ?DeleteContainerOptions $options = null): DeleteContainerResponse
    {
        try {
            $uri = $this->buildContainerUri($container);

            $query = $this->buildQuery([
                'restype' => 'container',
            ]);

            $this->client->delete($uri, [
                'query' => $query,
            ]);

            return new DeleteContainerResponse();
        } catch (RequestException $e) {
            throw $this->convertRequestException($e);
        }
    }

    public function listBlobs(string $container, ?ListBlobsOptions $options = null): ListBlobsResponse
    {
        try {
            $uri = $this->buildContainerUri($container);

            $query = $this->buildQuery([
                'restype' => 'container',
                'comp' => 'list',
                'prefix' => $options?->prefix ?? new MissingOption(),
                'marker' => $options?->marker ?? new MissingOption(),
                'maxresults' => $options?->maxResults ?? new MissingOption(),
            ]);

            $response = $this->client->get($uri, ['query' => $query]);

            return $this->deserializeBody($response->getBody(), ListBlobsResponse::class);
        } catch (RequestException $e) {
            throw $this->convertRequestException($e);
        }
    }

    public function uploadBlockBlob(string $container, string $blob, $content, ?UploadBlockBlobOptions $options = null): void
    {
        $stream = StreamUtils::streamFor($content);

        if ($stream->getSize() > $this->singleBlobUploadThreshold) {
            $this->uploadBlockBlobInChunks($container, $blob, $stream, $options);
        } else {
            $this->putBlob($container, $blob, $content, new PutBlobOptions($options?->contentType));
        }
    }

    private function uploadBlockBlobInChunks(string $container, string $blob, StreamInterface $content, ?UploadBlockBlobOptions $options = null): void
    {
        $blocks = [];
        $contentSize = $content->getSize();

        if ($contentSize === null) {
            throw new \Exception("Invalid stream");
        }

        $blockSize = $this->getParallelBlobUploadBlockSize($contentSize);

        $putBlockRequestGenerator = function () use ($container, $blob, $content, $blockSize, &$blocks): \Iterator {
            while (! $content->eof()) {
                $blockContent = $content->read($blockSize);

                $blockId = str_pad((string) count($blocks), 6, '0', STR_PAD_LEFT);
                $block = new Block($blockId, BlockType::UNCOMMITTED);
                $blocks[] = $block;

                yield fn () => $this->putBlockAsync($container, $blob, $block, StreamUtils::streamFor($blockContent));
            }
        };

        $pool = new Pool($this->client, $putBlockRequestGenerator(), [
            'rejected' => function (RequestException $e) {
                throw $this->convertRequestException($e);
            },
        ]);

        $pool->promise()->wait();

        $this->putBlockList($container, $blob, $blocks, new PutBlockListOptions($options?->contentType));
    }

    private function putBlockAsync(string $container, string $blob, Block $block, StreamInterface $content, ?PutBlockOptions $options = null): PromiseInterface
    {
        try {
            $uri = $this->buildBlobUri($container, $blob);

            $query = $this->buildQuery([
                'comp' => 'block',
                'blockid' => base64_encode($block->id),
            ]);

            return $this->client
                ->putAsync($uri, [
                    'query' => $query,
                    'body' => $content,
                ])
                ->then(function () {
                    return new PutBlockResponse();
                });
        } catch (RequestException $e) {
            throw $this->convertRequestException($e);
        }
    }

    public function putBlock(string $container, string $blob, Block $block, $content, ?PutBlockOptions $options = null): PutBlockResponse
    {
        /** @phpstan-ignore-next-line */
        return $this->putBlockAsync($container, $blob, $block, StreamUtils::streamFor($content), $options)->wait();
    }

    public function putBlockList(string $container, string $blob, array $blocks, ?PutBlockListOptions $options = null): PutBlockListResponse
    {
        try {
            $uri = $this->buildBlobUri($container, $blob);

            $query = $this->buildQuery([
                'comp' => 'blocklist',
            ]);

            $headers = $this->buildHeaders([
                'x-ms-blob-content-type' => $options?->contentType ?? new MissingOption(),
            ]);

            $blockList = [];
            foreach ($blocks as $block) {
                switch ($block->type) {
                    case BlockType::COMMITTED:
                        $blockList['Committed'][] = base64_encode($block->id);
                        break;
                    case BlockType::UNCOMMITTED:
                        $blockList['Uncommitted'][] = base64_encode($block->id);
                        break;
                    case BlockType::LATEST:
                        $blockList['Latest'][] = base64_encode($block->id);
                }
            }

            $body = $this->serializeBody('BlockList', $blockList);

            $this->client->put($uri, [
                'query' => $query,
                'headers' => $headers,
                'body' => $body,
            ]);

            return new PutBlockListResponse();
        } catch (RequestException $e) {
            throw $this->convertRequestException($e);
        }
    }

    public function getBlob(string $container, string $blob, ?GetBlobOptions $options = null): GetBlobResponse
    {
        try {
            $uri = $this->buildBlobUri($container, $blob);

            $response = $this->client->get($uri, [
                'stream' => true,
            ]);

            return new GetBlobResponse(
                $response->getBody(),
                new \DateTime($response->getHeader('Last-Modified')[0]),
                (int) $response->getHeader('Content-Length')[0],
                $response->getHeader('Content-Type')[0],
                $response->getHeader('Content-MD5')[0],
            );
        } catch (RequestException $e) {
            throw $this->convertRequestException($e);
        }
    }

    public function getBlobProperties(string $container, string $blob, ?GetBlobPropertiesOptions $options = null): GetBlobPropertiesResponse
    {
        try {
            $uri = $this->buildBlobUri($container, $blob);

            $response = $this->client->head($uri);

            return new GetBlobPropertiesResponse(
                new \DateTime($response->getHeader('Last-Modified')[0]),
                (int) $response->getHeader('Content-Length')[0],
                $response->getHeader('Content-Type')[0],
            );
        } catch (RequestException $e) {
            throw $this->convertRequestException($e);
        }
    }

    public function putBlob(string $container, string $blob, $content, ?PutBlobOptions $options = null): PutBlobResponse
    {
        try {
            $uri = $this->buildBlobUri($container, $blob);

            $headers = $this->buildHeaders([
                'x-ms-blob-type' => 'BlockBlob',
                'Content-Type' => $options?->contentType ?? new MissingOption(),
            ]);

            $this->client->put($uri, [
                'headers' => $headers,
                'body' => $content,
            ]);

            return new PutBlobResponse();
        } catch (RequestException $e) {
            throw $this->convertRequestException($e);
        }
    }

    public function deleteBlob(string $container, string $blob, ?DeleteBlobOptions $options = null): DeleteBlobResponse
    {
        try {
            $uri = $this->buildBlobUri($container, $blob);

            $this->client->delete($uri);

            return new DeleteBlobResponse();
        } catch (RequestException $e) {
            throw $this->convertRequestException($e);
        }
    }

    public function containerExists(string $container): bool
    {
        try {
            $this->getContainerProperties($container);

            return true;
        } catch (ContainerNotFoundException) {
            return false;
        }
    }

    public function blobExists(string $container, string $blob): bool
    {
        try {
            $this->getBlobProperties($container, $blob);

            return true;
        } catch (BlobNotFoundException) {
            return false;
        }
    }

    private function convertRequestException(RequestException $e): \Throwable
    {
        return match (ErrorCode::fromRequestException($e)) {
            ErrorCode::AUTHORIZATION_FAILURE => new AuthorizationFailedException($e),
            ErrorCode::CONTAINER_NOT_FOUND => new ContainerNotFoundException($e),
            ErrorCode::CONTAINER_ALREADY_EXISTS => new ContainerAlreadyExistsException($e),
            ErrorCode::BLOB_NOT_FOUND => new BlobNotFoundException($e),
            ErrorCode::INVALID_BLOCK_LIST => new InvalidBlockListException($e),
            default => $e
        };
    }
}
