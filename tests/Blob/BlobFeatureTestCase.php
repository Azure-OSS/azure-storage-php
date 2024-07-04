<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob;

use AzureOss\Storage\Blob\Clients\BlobServiceClient;
use AzureOss\Storage\Blob\Clients\ContainerClient;
use AzureOss\Storage\Blob\Exceptions\BlobNotFoundException;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

class BlobFeatureTestCase extends TestCase
{
    protected BlobServiceClient $serviceClient;

    protected function setUp(): void
    {
        $connectionString = getenv('AZURE_STORAGE_BLOB_TEST_CONNECTION_STRING');

        if (! is_string($connectionString)) {
            throw new \Exception('Connection string not set!');
        }

        $this->serviceClient = BlobServiceClient::fromConnectionString($connectionString);
    }

    protected function withContainer(string $method, callable $callable): void
    {
        $container = substr(md5($method), 0, 24);
        $client = $this->serviceClient->getContainerClient($container);

        try {
            $client->delete();
        } catch (ContainerNotFoundException) {
            // do nothing
        }

        $client->create();

        $callable($client);

        try {
            $client->delete();
        } catch (ContainerNotFoundException) {
            // do nothing
        }
    }

    protected function withBlob(string $method, callable $callable): void
    {
        $this->withContainer($method, function (ContainerClient $containerClient) use ($method, $callable) {
            $blob = md5($method);

            $client = $containerClient->getBlobClient($blob);

            try {
                $client->delete();
            } catch (BlobNotFoundException) {
                // do nothing
            }

            $client->put('Lorem ipsum dolor sit amet, consectetur adipiscing elit.');

            $callable($client);
        });
    }

    protected function withFile(string $method, int $size, callable $callable): void
    {
        $path = sys_get_temp_dir() . '/' . md5($method);

        unlink($path);
        $resource = Utils::streamFor(Utils::tryFopen($path, 'w'));

        $chunk = 1000;
        while ($size > 0) {
            $chunkContent = str_pad('', min($chunk, $size));
            $resource->write($chunkContent);
            $size -= $chunk;
        }
        $resource->close();

        $callable(Utils::streamFor(Utils::tryFopen($path, 'r')));

        unlink($path);
    }
}
