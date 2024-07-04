<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature\BlobClient;

use AzureOss\Storage\Blob\Clients\BlobClient;
use AzureOss\Storage\Blob\Clients\ContainerClient;
use AzureOss\Storage\Blob\Exceptions\BlobNotFoundException;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class GetBlobTest extends BlobFeatureTestCase
{
    #[Test]
    public function gets_blob(): void
    {
        $this->expectNotToPerformAssertions();

        $this->withBlob(__METHOD__, function (BlobClient $blobClient) {
            $blobClient->get();
        });
    }

    #[Test]
    public function throws_when_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient('noop')->getBlobClient('noop')->get();
    }

    #[Test]
    public function throws_when_blob_doesnt_exist(): void
    {
        $this->expectException(BlobNotFoundException::class);

        $this->withContainer(__METHOD__, function (ContainerClient $containerClient) {
            $containerClient->getBlobClient('noop')->get();
        });
    }
}
