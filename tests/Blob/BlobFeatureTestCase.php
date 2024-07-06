<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob;

use AzureOss\Storage\Blob\BlobServiceClient;
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

    protected function withFile(int $size, callable $callable): void
    {
        $path = sys_get_temp_dir() . '/azure-oss-test-file';

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
