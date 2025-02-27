<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Benchmark;

use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Tests\Utils\FileFactory;
use GuzzleHttp\Psr7\Utils;
use PhpBench\Attributes\AfterClassMethods;
use PhpBench\Attributes\Assert;
use PhpBench\Attributes\ParamProviders;

#[AfterClassMethods('cleanTestFiles')]
final class BlobClientBench
{
    public static function cleanTestFiles(): void
    {
        FileFactory::clean();
    }

    /**
     * @param array{ path: string, count: int } $params
     */
    #[ParamProviders('provideFiles')]
    #[Assert("mode(variant.mem.peak) < 15 megabytes")]
    public function benchUpload(array $params): void
    {
        $serviceClient = BlobServiceClient::fromConnectionString("UseDevelopmentStorage=true");
        $containerClient = $serviceClient->getContainerClient("benchmark");
        $containerClient->createIfNotExists();

        $blobClient = $containerClient->getBlobClient("benchmark");

        for ($i = 0; $i < $params['count']; $i++) {
            $file = Utils::tryFopen($params['path'], 'r');

            $blobClient->upload($file);
        }
    }

    public function provideFiles(): \Generator
    {
        yield '20x10KB' => ['path' => FileFactory::create(10_000), 'count' => 100];
        yield '10x10MB' => ['path' => FileFactory::create(10_000_000), 'count' => 10];
        yield '5x100MB' => ['path' => FileFactory::create(100_000_000), 'count' => 5];
        yield '2x1GB' => ['path' => FileFactory::create(1_000_000_000), 'count' => 2];
        yield '1x4GB' => ['path' => FileFactory::create(4_000_000_000), 'count' => 1];
    }
}
