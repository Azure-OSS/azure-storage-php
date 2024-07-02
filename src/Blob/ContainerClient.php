<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob;

use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Blob\Requests\ContainerExistsOptions;
use AzureOss\Storage\Blob\Requests\CreateContainerOptions;
use AzureOss\Storage\Blob\Requests\DeleteContainerOptions;
use AzureOss\Storage\Blob\Requests\GetContainerPropertiesOptions;
use AzureOss\Storage\Blob\Requests\ListBlobsOptions;
use AzureOss\Storage\Blob\Responses\CreateContainerResponse;
use AzureOss\Storage\Blob\Responses\DeleteContainerResponse;
use AzureOss\Storage\Blob\Responses\GetContainerPropertiesResponse;
use AzureOss\Storage\Blob\Responses\ListBlobsResponse;
use AzureOss\Storage\Blob\Responses\XmlDecodable;
use AzureOss\Storage\Common\Auth\Credentials;
use AzureOss\Storage\Common\MiddlewareFactory;
use AzureOss\Storage\Common\ExceptionHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

class ContainerClient
{
    private readonly Client $client;
    private HandlerStack $handlerStack;

    private readonly ExceptionHandler $exceptionHandler;

    public function __construct(
        public readonly string $blobEndpoint,
        public readonly string $containerName,
        public readonly Credentials $credentials
    ) {
        $this->handlerStack = (new MiddlewareFactory())->create(BlobServiceClient::API_VERSION, $credentials);
        $this->client = new Client(['handler' => $this->handlerStack]);
        $this->exceptionHandler = new ExceptionHandler();
    }

    public function getBlobClient(string $blobName): BlobClient
    {
        return new BlobClient(
            $this->blobEndpoint,
            $this->containerName,
            $blobName,
            $this->credentials,
        );
    }

    public function getBlockBlobClient(string $blobName): BlockBlobClient
    {
        return new BlockBlobClient(
            $this->blobEndpoint,
            $this->containerName,
            $blobName,
            $this->credentials,
        );
    }

    private function getUrl(): string
    {
        return $this->blobEndpoint . '/' . $this->containerName;
    }

    public function create(?CreateContainerOptions $options = null): CreateContainerResponse
    {
        try {
            $this->client->put($this->getUrl(), [
                'query' => [
                    'restype' => 'container',
                ]
            ]);

            return new CreateContainerResponse();
        } catch (RequestException $e) {
            $this->exceptionHandler->handleRequestException($e);
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
            $this->exceptionHandler->handleRequestException($e);
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
            $this->exceptionHandler->handleRequestException($e);
        }
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
                ]
            ]);

            return $this->decodeBody($response->getBody(), ListBlobsResponse::class);
        } catch (RequestException $e) {
            $this->exceptionHandler->handleRequestException($e);
        }
    }

    public function exists(ContainerExistsOptions $options = null): bool
    {
        try {
            $this->getProperties();

            return true;
        } catch (ContainerNotFoundException) {
            return false;
        }
    }

    /**
     * @template T of XmlDecodable
     * @param class-string<T> $type
     * @return T
     */
    private function decodeBody(StreamInterface $body, string $type): mixed
    {
        $parsed = (new XmlEncoder())->decode($body->getContents(), 'xml');

        /** @phpstan-ignore-next-line */
        return $type::fromXml($parsed);
    }
}
