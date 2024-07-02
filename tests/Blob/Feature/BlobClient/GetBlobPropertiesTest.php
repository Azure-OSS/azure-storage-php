<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature\BlobClient;

use AzureOss\Storage\Blob\BlobClient;
use AzureOss\Storage\Blob\ContainerClient;
use AzureOss\Storage\Blob\Exceptions\BlobNotFoundException;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class GetBlobPropertiesTest extends BlobFeatureTestCase
{
    #[Test]
    public function gets_blob_properties(): void
    {
        $this->expectNotToPerformAssertions();

        $this->withBlob(__METHOD__, function (BlobClient $blobClient) {
            $blobClient->getProperties();
        });
    }

    #[Test]
    public function throws_when_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient('noop')->getBlobClient('noop')->getProperties();
    }

    #[Test]
    public function throws_when_blob_doesnt_exist(): void
    {
        $this->expectException(BlobNotFoundException::class);

        $this->withContainer(__METHOD__, function (ContainerClient $containerClient) {
            $containerClient->getBlobClient('noop')->getProperties();
        });
    }
}
