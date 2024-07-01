<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Feature;

use AzureOss\Storage\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Requests\UploadBlockBlobOptions;
use AzureOss\Storage\Tests\FeatureTestCase;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\StreamInterface;

class UploadBlockBlobTest extends FeatureTestCase
{
    #[Test]
    public function uploads_small_file_in_single_request(): void
    {
        $this->withContainer(__METHOD__, function (string $container) {
            $this->withFile(__METHOD__, 32, function (StreamInterface $file) use ($container) {
                $history = [];
                $this->client->getHandlerStack()->push(Middleware::history($history));
                $this->client->setSingleBlobUploadThreshold(32);

                $this->client->uploadBlockBlob($container, 'test', $file, new UploadBlockBlobOptions('application/pdf'));

                $this->assertCount(1, $history);

                $blobProps = $this->client->getBlob($container, 'test');

                $this->assertEquals(32, $blobProps->contentLength);
                $this->assertEquals((string) $file, (string) Utils::streamFor($blobProps->content));
                $this->assertEquals('application/pdf', $blobProps->contentType);
            });
        });
    }

    #[Test]
    public function uploads_large_file_in_multiple_requests(): void
    {
        $this->withContainer(__METHOD__, function (string $container) {
            $this->withFile(__METHOD__, 64, function (StreamInterface $file) use ($container) {
                $history = [];
                $this->client->getHandlerStack()->push(Middleware::history($history));
                $this->client->setSingleBlobUploadThreshold(32);
                $this->client->setParallelBlobUploadBlobSizeThresholds([8]);

                $this->client->uploadBlockBlob($container, 'test', $file, new UploadBlockBlobOptions('application/pdf'));

                $this->assertTrue(count($history) > 1);

                $blobProps = $this->client->getBlob($container, 'test');

                $this->assertEquals(64, $blobProps->contentLength);
                $this->assertEquals((string) $file, (string) Utils::streamFor($blobProps->content));
                $this->assertEquals('application/pdf', $blobProps->contentType);
            });
        });
    }

    #[Test]
    public function throws_when_container_does_not_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->client->uploadBlockBlob('noop', 'noop', 'Lorem');
    }
}
