<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature\BlobClient;

use AzureOss\Storage\Blob\Clients\BlobClient;
use AzureOss\Storage\Blob\Clients\BlobContainerClient;
use AzureOss\Storage\Blob\Exceptions\BlobNotFoundException;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class DeleteBlobTest extends BlobFeatureTestCase
{
    #[Test]
    public function deletes_blob(): void
    {
        $this->withBlob(__METHOD__, function (BlobClient $blobClient) {
            $this->assertTrue($blobClient->exists());

            $blobClient->delete();

            $this->assertFalse($blobClient->exists());
        });
    }

    #[Test]
    public function throws_when_container_does_not_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient('noop')->getBlobClient('noop')->delete();
    }

    #[Test]
    public function throws_when_blob_does_not_exist(): void
    {
        $this->expectException(BlobNotFoundException::class);

        $this->withContainer(__METHOD__, function (BlobContainerClient $containerClient) {
            $containerClient->getBlobClient('noop')->delete();
        });
    }
}
