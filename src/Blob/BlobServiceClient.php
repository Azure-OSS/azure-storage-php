<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob;

use AzureOss\Storage\Blob\Exceptions\BlobStorageExceptionFactory;
use AzureOss\Storage\Blob\Exceptions\InvalidConnectionStringException;
use AzureOss\Storage\Blob\Models\BlobContainer;
use AzureOss\Storage\Blob\Responses\ListContainersResponseBody;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Common\Helpers\ConnectionStringHelper;
use AzureOss\Storage\Common\Middleware\ClientFactory;
use AzureOss\Storage\Common\Serializer\SerializerFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\UriInterface;

final class BlobServiceClient
{
    private readonly Client $client;

    private readonly BlobStorageExceptionFactory $exceptionFactory;

    private readonly SerializerInterface $serializer;

    public function __construct(
        public UriInterface $uri,
        public readonly ?StorageSharedKeyCredential $sharedKeyCredentials = null,
    ) {
        $this->client = (new ClientFactory())->create($uri, $sharedKeyCredentials);
        $this->serializer = (new SerializerFactory())->create();
        $this->exceptionFactory = new BlobStorageExceptionFactory($this->serializer);
    }

    public static function fromConnectionString(string $connectionString): self
    {
        $uri = ConnectionStringHelper::getBlobEndpoint($connectionString);
        if ($uri === null) {
            throw new InvalidConnectionStringException();
        }

        $sas = ConnectionStringHelper::getSas($connectionString);
        if($sas !== null) {
            return new self($uri->withQuery($sas));
        }

        $accountName = ConnectionStringHelper::getAccountName($connectionString);
        $accountKey = ConnectionStringHelper::getAccountKey($connectionString);
        if ($accountName !== null && $accountKey !== null) {
            return new self($uri, new StorageSharedKeyCredential($accountName, $accountKey));
        }

        throw new InvalidConnectionStringException();
    }

    public function getContainerClient(string $containerName): BlobContainerClient
    {
        return new BlobContainerClient(
            $this->uri->withPath($this->uri->getPath() . "/" . $containerName),
            $this->sharedKeyCredentials,
        );
    }

    /**
     * @return \Iterator<int, BlobContainer>
     */
    public function getBlobContainers(?string $prefix = null): \Iterator
    {
        try {
            $nextMarker = "";

            while(true) {
                $response = $this->client->get($this->uri, [
                    'query' => [
                        'comp' => 'list',
                        'marker' => $nextMarker,
                        'prefix' => $prefix,
                    ],
                ]);
                /** @var ListContainersResponseBody $body */
                $body = $this->serializer->deserialize($response->getBody()->getContents(), ListContainersResponseBody::class, 'xml');
                $nextMarker = $body->nextMarker;

                foreach ($body->containers as $container) {
                    yield $container;
                }

                if ($nextMarker === "") {
                    break;
                }
            }
        } catch (RequestException $e) {
            throw $this->exceptionFactory->create($e);
        }
    }
}
