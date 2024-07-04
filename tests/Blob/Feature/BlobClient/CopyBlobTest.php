<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature\BlobClient;

use AzureOss\Storage\Blob\Clients\BlobClient;
use AzureOss\Storage\Blob\Clients\ContainerClient;
use AzureOss\Storage\Blob\Exceptions\BlobNotFoundException;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class CopyBlobTest extends BlobFeatureTestCase
{
    #[Test]
    public function copies_blob(): void
    {
        $this->withBlob(__METHOD__, function (BlobClient $sourceBlobClient) {
            $this->withContainer(__METHOD__, function (ContainerClient $targetContainerClient) use ($sourceBlobClient) {
                $targetBlob = "copy";
                $sourceBlobClient->copy($targetContainerClient->containerName, $targetBlob);

                $this->assertTrue($sourceBlobClient->exists());
                $this->assertTrue($targetContainerClient->getBlobClient($targetBlob)->exists());

                $this->assertEquals(
                    $sourceBlobClient->get()->content->getContents(),
                    $targetContainerClient->getBlobClient($targetBlob)->get()->content->getContents(),
                );
            });
        });
    }

    #[Test]
    public function throws_when_source_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->withContainer(__METHOD__, function (ContainerClient $sourceContainerClient) {
            $this->serviceClient->getContainerClient("noop")->getBlobClient('noop')->copy($sourceContainerClient->containerName, "copy");
        });
    }

    #[Test]
    public function throws_when_source_blob_doesnt_exist(): void
    {
        $this->expectException(BlobNotFoundException::class);

        $this->withContainer(__METHOD__, function (ContainerClient $sourceContainerClient) {
            $this->withContainer(__METHOD__, function (ContainerClient $targetContainerClient) use ($sourceContainerClient) {
                $sourceContainerClient->getBlobClient('noop')->copy($targetContainerClient->containerName, "noop");
            });
        });
    }

    #[Test]
    public function throws_when_target_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->withBlob(__METHOD__, function (BlobClient $sourceBlobClient) {
            $sourceBlobClient->copy("noop", "copy");
        });
    }
}
