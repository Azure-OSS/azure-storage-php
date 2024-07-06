<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Benchmark;

use AzureOss\Storage\Blob\BlobServiceClient;
use GuzzleHttp\Psr7\Utils;
use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\OutputTimeUnit;

class BlobClientBench
{
    private const TEST_FILE_SIZE = 1_000_000_000;

    private string $testFilePath;

    #[BeforeMethods("createTestFile")]
    #[AfterMethods("deleteTestFile")]
    #[OutputTimeUnit("seconds")]
    public function benchUpload(): void
    {
        $serviceClient = BlobServiceClient::fromConnectionString("UseDevelopmentStorage=true");
        $containerClient = $serviceClient->getContainerClient("benchmark");
        $containerClient->createIfNotExists();

        $blobClient = $containerClient->getBlobClient("benchmark");

        $file = Utils::tryFopen($this->testFilePath, 'r');

        $blobClient->upload($file);
    }

    public function createTestFile(): void
    {
        $size = self::TEST_FILE_SIZE;
        $path = sys_get_temp_dir() . '/azure-oss-bench-test-file';

        $resource = Utils::streamFor(Utils::tryFopen($path, 'w'));

        $chunk = 10000;
        while ($size > 0) {
            $chunkContent = str_pad('', min($chunk, $size));
            $resource->write($chunkContent);
            $size -= $chunk;
        }
        $resource->close();

        $this->testFilePath = $path;
    }

    public function deleteTestFile(): void
    {
        unlink($this->testFilePath);
    }
}
