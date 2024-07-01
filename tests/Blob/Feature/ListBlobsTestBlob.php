<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class ListBlobsTestBlob extends BlobFeatureTestCase
{
    #[Test]
    public function gets_blobs(): void
    {
        $this->expectNotToPerformAssertions();

        $this->withContainer(__METHOD__, function (string $container) {
            $this->client->listBlobs($container);
        });
    }

    #[Test]
    public function throws_exception_when_container_already_exists()
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->client->listBlobs('noop');
    }
}
