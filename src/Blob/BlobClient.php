<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob;

use AzureOss\Storage\Blob\Exceptions\BlobNotFoundException;
use AzureOss\Storage\Blob\Options\CopyBlobOptions;
use AzureOss\Storage\Blob\Options\DeleteBlobOptions;
use AzureOss\Storage\Blob\Options\GetBlobOptions;
use AzureOss\Storage\Blob\Options\GetBlobPropertiesOptions;
use AzureOss\Storage\Blob\Options\PutBlobOptions;
use AzureOss\Storage\Blob\Responses\CopyBlobResponse;
use AzureOss\Storage\Blob\Responses\DeleteBlobIfExistsResponse;
use AzureOss\Storage\Blob\Responses\DeleteBlobResponse;
use AzureOss\Storage\Blob\Responses\GetBlobPropertiesResponse;
use AzureOss\Storage\Blob\Responses\GetBlobResponse;
use AzureOss\Storage\Blob\Responses\PutBlobResponse;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Common\Exceptions\ExceptionFactory;
use AzureOss\Storage\Common\Middleware\MiddlewareFactory;
use AzureOss\Storage\Tests\Blob\Feature\BlobClient\BlobExistsTest;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\StreamInterface;

final class BlobClient
{
    private readonly Client $client;

    private HandlerStack $handlerStack;

    private readonly ExceptionFactory $exceptionFactory;

    public function __construct(
        public readonly string $blobEndpoint,
        public readonly string $containerName,
        public readonly string $blobName,
        public StorageSharedKeyCredential $sharedKeyCredentials
    ) {
        $this->handlerStack = (new MiddlewareFactory())->create($sharedKeyCredentials);
        $this->client = new Client(['handler' => $this->handlerStack]);
        $this->exceptionFactory = new ExceptionFactory();
    }

    public function getUrl(): string
    {
        return $this->blobEndpoint . '/' . $this->containerName . '/' . $this->blobName;
    }

    public function getBlockBlobClient(): BlockBlobClient
    {
        return new BlockBlobClient($this->blobEndpoint, $this->containerName, $this->blobName, $this->sharedKeyCredentials);
    }

    public function getContainerClient(): ContainerClient
    {
        return new ContainerClient($this->blobEndpoint, $this->containerName, $this->sharedKeyCredentials);
    }

    public function get(?GetBlobOptions $options = null): GetBlobResponse
    {
        try {
            $response = $this->client->get($this->getUrl(), [
                'stream' => true,
            ]);

            return new GetBlobResponse(
                $response->getBody(),
                new \DateTime($response->getHeader('Last-Modified')[0]),
                (int) $response->getHeader('Content-Length')[0],
                $response->getHeader('Content-Type')[0],
            );
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    public function getProperties(?GetBlobPropertiesOptions $options = null): GetBlobPropertiesResponse
    {
        try {
            $response = $this->client->head($this->getUrl());

            return new GetBlobPropertiesResponse(
                new \DateTime($response->getHeaderLine('Last-Modified')),
                (int) $response->getHeaderLine('Content-Length'),
                $response->getHeaderLine('Content-Type'),
            );
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    /**
     * @param string|resource|StreamInterface $content
     */
    public function put($content, ?PutBlobOptions $options = null): PutBlobResponse
    {
        try {
            $this->client->put($this->getUrl(), [
                'headers' => [
                    'x-ms-blob-type' => 'BlockBlob',
                    'Content-Type' => $options?->contentType,
                ],
                'body' => $content,
            ]);

            return new PutBlobResponse();
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    public function delete(?DeleteBlobOptions $options = null): DeleteBlobResponse
    {
        try {
            $this->client->delete($this->getUrl());

            return new DeleteBlobResponse();
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    public function deleteIfExists(?DeleteBlobOptions $options = null): DeleteBlobIfExistsResponse
    {
        try {
            $this->delete($options);
        } catch (BlobNotFoundException) {
            // do nothing
        }

        return new DeleteBlobIfExistsResponse();
    }

    public function copy(string $targetContainer, string $targetBlob, ?CopyBlobOptions $options = null): CopyBlobResponse
    {
        try {
            $this->client->put($this->blobEndpoint . '/' . $targetContainer . '/' . $targetBlob, [
                'headers' => [
                    'x-ms-copy-source' => $this->blobEndpoint . '/'  . $this->containerName . '/' . $this->blobName
                ]
            ]);

            return new CopyBlobResponse();
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    public function exists(BlobExistsTest $options = null): bool
    {
        try {
            $this->getProperties();

            return true;
        } catch (BlobNotFoundException) {
            return false;
        }
    }
}
