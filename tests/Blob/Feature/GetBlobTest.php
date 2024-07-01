<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

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

        $this->withBlob(__METHOD__, function (string $containers, string $blob) {
            $this->client->getBlob($containers, $blob);
        });
    }

    #[Test]
    public function throws_when_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->client->getBlob('noop', 'noop');
    }

    #[Test]
    public function throws_when_blob_doesnt_exist(): void
    {
        $this->expectException(BlobNotFoundException::class);

        $this->withContainer(__METHOD__, function (string $container) {
            $this->client->getBlob($container, 'noop');
        });
    }
}
