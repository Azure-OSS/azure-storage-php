<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class ListBlobsTest extends BlobFeatureTestCase
{
    #[Test]
    public function gets_blobs(): void
    {
        $this->withBlob(__METHOD__, function (string $container) {
            $blobs = $this->client->listBlobs($container);

            $this->assertCount(1, $blobs->blobs);
        });
    }

    #[Test]
    public function throws_exception_when_container_already_exists(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->client->listBlobs('noop');
    }
}
