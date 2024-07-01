<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

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

        $this->withBlob(__METHOD__, function (string $containers, string $blobs) {
            $this->client->getBlobProperties($containers, $blobs);
        });
    }

    #[Test]
    public function throws_when_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->client->getBlobProperties('noop', 'noop');
    }

    #[Test]
    public function throws_when_blob_doesnt_exist(): void
    {
        $this->expectException(BlobNotFoundException::class);

        $this->withContainer(__METHOD__, function (string $container) {
            $this->client->getBlobProperties($container, 'noop');
        });
    }
}
