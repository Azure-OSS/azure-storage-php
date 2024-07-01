<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests;

use AzureOss\Storage\Auth\SharedKeyAuthScheme;
use AzureOss\Storage\BlobApiClient;
use AzureOss\Storage\Exceptions\BlobNotFoundException;
use AzureOss\Storage\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\StorageServiceSettings;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

class FeatureTestCase extends TestCase
{
    protected BlobApiClient $client;

    protected string $container;

    protected function setUp(): void
    {
        $connectionString = getenv('AZURE_STORAGE_BLOB_TEST_CONNECTION_STRING');

        if (! is_string($connectionString)) {
            throw new \Exception('Connection string not set!');
        }

        $settings = StorageServiceSettings::createFromConnectionString($connectionString);
        $auth = new SharedKeyAuthScheme($settings);

        $this->client = new BlobApiClient($settings, $auth);
    }

    protected function withContainer(string $method, callable $callable): void
    {
        $container = substr(md5($method), 0, 24);

        try {
            $this->client->deleteContainer($container);
        } catch (ContainerNotFoundException) {
            // do nothing
        }

        $this->client->createContainer($container);

        $callable($container);

        try {
            $this->client->deleteContainer($container);
        } catch (ContainerNotFoundException) {
            // do nothing
        }
    }

    protected function withBlob(string $method, callable $callable): void
    {
        $this->withContainer($method, function (string $container) use ($method, $callable) {
            $blob = md5($method);

            try {
                $this->client->deleteBlob($container, $blob);
            } catch (BlobNotFoundException) {
                // do nothing
            }

            $this->client->putBlob($container, $blob, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.');

            $callable($container, $blob);
        });
    }

    protected function withFile(string $method, int $size, callable $callable): void
    {
        $path = sys_get_temp_dir().'/'.md5($method);

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
