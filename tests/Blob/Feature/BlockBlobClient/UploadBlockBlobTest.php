<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature\BlockBlobClient;

use AzureOss\Storage\Blob\ContainerClient;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Blob\Requests\UploadBlockBlobOptions;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use GuzzleHttp\Middleware;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\StreamInterface;

class UploadBlockBlobTest extends BlobFeatureTestCase
{
    #[Test]
    public function uploads_small_file_in_single_request(): void
    {
        $this->withContainer(__METHOD__, function (ContainerClient $containerClient) {
            $this->withFile(__METHOD__, 32, function (StreamInterface $file) use ($containerClient) {
                $blockClient = $containerClient->getBlockBlobClient('test');

                $history = [];
                $blockClient->getHandlerStack()->push(Middleware::history($history));
                $blockClient->setSingleUploadThreshold(32);

                $blockClient->upload($file, new UploadBlockBlobOptions('application/pdf'));

                $this->assertCount(0, $history); // blob client put gets called instead

                $blobProps = $blockClient->getBlobClient()->get();

                $this->assertEquals(32, $blobProps->contentLength);
                $this->assertEquals('application/pdf', $blobProps->contentType);
                $this->assertEquals((string) $file, (string) $blobProps->content);
            });
        });
    }

    #[Test]
    public function uploads_large_file_in_multiple_requests(): void
    {
        $this->withContainer(__METHOD__, function (ContainerClient $containerClient) {
            $this->withFile(__METHOD__, 64, function (StreamInterface $file) use ($containerClient) {
                $blockClient = $containerClient->getBlockBlobClient('test');

                $history = [];
                $blockClient->getHandlerStack()->push(Middleware::history($history));
                $blockClient->setSingleUploadThreshold(32);
                $blockClient->setBlockSizeThresholds([8]);

                $blockClient->upload($file, new UploadBlockBlobOptions('application/pdf'));

                $this->assertTrue(count($history) > 1);

                $blobProps = $blockClient->getBlobClient()->get();

                $this->assertEquals(64, $blobProps->contentLength);
                $this->assertEquals((string) $file, (string) $blobProps->content);
                $this->assertEquals('application/pdf', $blobProps->contentType);
            });
        });
    }

    #[Test]
    public function throws_when_container_does_not_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient('noop')->getBlockBlobClient('noop')->upload('Lorem');
    }
}
