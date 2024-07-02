<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob;

use AzureOss\Storage\Blob\Exceptions\BlobNotFoundException;
use AzureOss\Storage\Blob\Requests\CopyBlobOptions;
use AzureOss\Storage\Blob\Requests\DeleteBlobOptions;
use AzureOss\Storage\Blob\Requests\GetBlobOptions;
use AzureOss\Storage\Blob\Requests\GetBlobPropertiesOptions;
use AzureOss\Storage\Blob\Requests\PutBlobOptions;
use AzureOss\Storage\Blob\Responses\CopyBlobResponse;
use AzureOss\Storage\Blob\Responses\DeleteBlobResponse;
use AzureOss\Storage\Blob\Responses\GetBlobPropertiesResponse;
use AzureOss\Storage\Blob\Responses\GetBlobResponse;
use AzureOss\Storage\Blob\Responses\PutBlobResponse;
use AzureOss\Storage\Common\Auth\Credentials;
use AzureOss\Storage\Common\MiddlewareFactory;
use AzureOss\Storage\Common\ExceptionHandler;
use AzureOss\Storage\Tests\Blob\Feature\BlobClient\BlobExistsTest;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;

class BlobClient
{
    private readonly Client $client;

    private HandlerStack $handlerStack;

    private readonly ExceptionHandler $exceptionHandler;

    public function __construct(
        public readonly string $blobEndpoint,
        public readonly string $containerName,
        public readonly string $blobName,
        public Credentials $credentials
    ) {
        $this->handlerStack = (new MiddlewareFactory())->create(BlobServiceClient::API_VERSION, $credentials);
        $this->client = new Client(['handler' => $this->handlerStack]);
        $this->exceptionHandler = new ExceptionHandler();
    }

    private function getUrl(): string
    {
        return $this->blobEndpoint . '/' . $this->containerName . '/' . $this->blobName;
    }

    public function getBlockBlobClient(): BlockBlobClient
    {
        return new BlockBlobClient($this->blobEndpoint, $this->containerName, $this->blobName, $this->credentials);
    }

    public function getContainerClient(): ContainerClient
    {
        return new ContainerClient($this->blobEndpoint, $this->containerName, $this->credentials);
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
            $this->exceptionHandler->handleRequestException($e);
        }
    }

    public function getProperties(?GetBlobPropertiesOptions $options = null): GetBlobPropertiesResponse
    {
        try {
            $response = $this->client->head($this->getUrl());

            return new GetBlobPropertiesResponse(
                new \DateTime($response->getHeader('Last-Modified')[0]),
                (int) $response->getHeader('Content-Length')[0],
                $response->getHeader('Content-Type')[0],
            );
        } catch (RequestException $e) {
            $this->exceptionHandler->handleRequestException($e);
        }
    }

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
            $this->exceptionHandler->handleRequestException($e);
        }
    }

    public function delete(?DeleteBlobOptions $options = null): DeleteBlobResponse
    {
        try {
            $this->client->delete($this->getUrl());

            return new DeleteBlobResponse();
        } catch (RequestException $e) {
            $this->exceptionHandler->handleRequestException($e);
        }
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
            $this->exceptionHandler->handleRequestException($e);
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
