<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob;

use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Blob\Options\ContainerExistsOptions;
use AzureOss\Storage\Blob\Options\CreateContainerOptions;
use AzureOss\Storage\Blob\Options\DeleteContainerOptions;
use AzureOss\Storage\Blob\Options\GetContainerPropertiesOptions;
use AzureOss\Storage\Blob\Options\ListBlobsOptions;
use AzureOss\Storage\Blob\Responses\CreateContainerResponse;
use AzureOss\Storage\Blob\Responses\DeleteContainerIfExistsResponse;
use AzureOss\Storage\Blob\Responses\DeleteContainerResponse;
use AzureOss\Storage\Blob\Responses\GetContainerPropertiesResponse;
use AzureOss\Storage\Blob\Responses\ListBlobsResponse;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Common\Exceptions\ExceptionFactory;
use AzureOss\Storage\Common\Middleware\MiddlewareFactory;
use AzureOss\Storage\Common\Serializer\SerializerFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use JMS\Serializer\SerializerInterface;

final class ContainerClient
{
    private readonly Client $client;

    private readonly HandlerStack $handlerStack;

    private readonly ExceptionFactory $exceptionFactory;

    private readonly SerializerInterface $serializer;

    public function __construct(
        public readonly string $blobEndpoint,
        public readonly string $containerName,
        public readonly StorageSharedKeyCredential $sharedKeyCredentials
    ) {
        $this->handlerStack = (new MiddlewareFactory())->create(BlobServiceClient::API_VERSION, $sharedKeyCredentials);
        $this->client = new Client(['handler' => $this->handlerStack]);
        $this->exceptionFactory = new ExceptionFactory();
        $this->serializer = (new SerializerFactory())->create();
    }

    public function getBlobClient(string $blobName): BlobClient
    {
        return new BlobClient(
            $this->blobEndpoint,
            $this->containerName,
            $blobName,
            $this->sharedKeyCredentials,
        );
    }

    public function getBlockBlobClient(string $blobName): BlockBlobClient
    {
        return new BlockBlobClient(
            $this->blobEndpoint,
            $this->containerName,
            $blobName,
            $this->sharedKeyCredentials,
        );
    }

    private function getUrl(): string
    {
        return $this->blobEndpoint.'/'.$this->containerName;
    }

    public function create(?CreateContainerOptions $options = null): CreateContainerResponse
    {
        try {
            $this->client->put($this->getUrl(), [
                'query' => [
                    'restype' => 'container',
                ],
            ]);

            return new CreateContainerResponse();
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    public function getProperties(?GetContainerPropertiesOptions $options = null): GetContainerPropertiesResponse
    {
        try {
            $this->client->head($this->getUrl(), [
                'query' => [
                    'restype' => 'container',
                ],
            ]);

            return new GetContainerPropertiesResponse();
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    public function delete(?DeleteContainerOptions $options = null): DeleteContainerResponse
    {
        try {
            $this->client->delete($this->getUrl(), [
                'query' => [
                    'restype' => 'container',
                ],
            ]);

            return new DeleteContainerResponse();
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    public function deleteIfExists(?DeleteContainerOptions $options = null): DeleteContainerIfExistsResponse
    {
        try {
            $this->delete($options);
        } catch (ContainerNotFoundException $e) {
            // do nothing
        }

        return new DeleteContainerIfExistsResponse();
    }

    public function listBlobs(?ListBlobsOptions $options = null): ListBlobsResponse
    {
        try {
            $response = $this->client->get($this->getUrl(), [
                'query' => [
                    'restype' => 'container',
                    'comp' => 'list',
                    'prefix' => $options?->prefix,
                    'marker' => $options?->marker,
                    'maxresults' => $options?->maxResults,
                    'delimiter' => $options?->delimiter,
                ],
            ]);

            /** @phpstan-ignore-next-line */
            return $this->serializer->deserialize($response->getBody()->getContents(), ListBlobsResponse::class, 'xml');
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }

    public function exists(?ContainerExistsOptions $options = null): bool
    {
        try {
            $this->getProperties();

            return true;
        } catch (ContainerNotFoundException) {
            return false;
        }
    }
}
