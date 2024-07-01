<?php

declare(strict_types=1);

namespace Brecht\FlysystemAzureBlobStorage\Tests\Feature;

use Brecht\FlysystemAzureBlobStorage\Exceptions\BlobNotFoundException;
use Brecht\FlysystemAzureBlobStorage\Exceptions\ContainerNotFoundException;
use Brecht\FlysystemAzureBlobStorage\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class DeleteBlobTest extends FeatureTestCase
{
    #[Test]
    public function deletes_blob(): void
    {
        $this->withBlob(__METHOD__, function (string $container, string $blob) {
            $this->assertTrue($this->client->blobExists($container, $blob));

            $this->client->deleteBlob($container, $blob);

            $this->assertFalse($this->client->blobExists($container, $blob));
        });
    }

    #[Test]
    public function throws_when_container_does_not_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->client->deleteBlob('noop', 'noop');
    }

    #[Test]
    public function throws_when_blob_does_not_exist(): void
    {
        $this->expectException(BlobNotFoundException::class);

        $this->withContainer(__METHOD__, function (string $container) {
            $this->client->deleteBlob($container, 'noop');
        });
    }
}
