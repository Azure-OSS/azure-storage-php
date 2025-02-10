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
     * @param array{ path: string } $params
     */
    #[ParamProviders('provideFiles')]
    #[Assert("mode(variant.mem.peak) < 15 megabytes")]
    public function benchUpload(array $params): void
    {
        $serviceClient = BlobServiceClient::fromConnectionString("UseDevelopmentStorage=true");
        $containerClient = $serviceClient->getContainerClient("benchmark");
        $containerClient->createIfNotExists();

        $blobClient = $containerClient->getBlobClient("benchmark");

        $file = Utils::tryFopen($params['path'], 'r');

        $blobClient->upload($file);
    }

    public function provideFiles(): \Generator
    {
        yield '100MB' => ['path' => FileFactory::create(100_000_000)];
        yield '200MB' => ['path' => FileFactory::create(200_000_000)];
        yield '400MB' => ['path' => FileFactory::create(400_000_000)];
        yield '800MB' => ['path' => FileFactory::create(800_000_000)];
        yield '1.6GB' => ['path' => FileFactory::create(1_600_000_000)];
    }
}
