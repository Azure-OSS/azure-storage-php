<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob;

use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Blob\Helpers\BlobUriParserHelper;
use PHPUnit\Framework\TestCase;

abstract class BlobFeatureTestCase extends TestCase
{
    protected BlobServiceClient $serviceClient;

    protected function setUp(): void
    {
        $connectionString = getenv('AZURE_STORAGE_BLOB_TEST_CONNECTION_STRING');

        if (!is_string($connectionString)) {
            throw new \Exception('Connection string not set!');
        }

        $this->serviceClient = BlobServiceClient::fromConnectionString($connectionString);
    }

    protected function randomContainerName(): string
    {
        return substr(md5((string) mt_rand()), 0, 7);
    }

    protected function cleanContainer(string $containerName): void
    {
        $containerClient = $this->serviceClient->getContainerClient($containerName);

        $containerClient->createIfNotExists();

        foreach ($containerClient->getBlobs() as $blob) {
            $containerClient->getBlobClient($blob->name)->delete();
        }
    }

    protected function markTestSkippedWhenUsingSimulator(): void
    {
        if (BlobUriParserHelper::isDevelopmentUri($this->serviceClient->uri)) {
            self::markTestSkipped("API unsupported in Azurite.");
        }
    }
}
