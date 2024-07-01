<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

use AzureOss\Storage\Blob\Exceptions\BlobNotFoundException;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class CopyBlobTest extends BlobFeatureTestCase
{
    #[Test]
    public function copies_blob(): void
    {
        $this->withBlob(__METHOD__, function (string $sourceContainer, string $sourceBlob) {
            $this->withContainer(__METHOD__, function (string $targetContainer) use ($sourceContainer, $sourceBlob) {
                $targetBlob = "copy";
                $this->client->copyBlob($sourceContainer, $sourceBlob, $targetContainer, $targetBlob);

                $this->assertTrue($this->client->blobExists($sourceContainer, $sourceBlob));
                $this->assertTrue($this->client->blobExists($targetContainer, $targetBlob));

                $this->assertEquals(
                    $this->client->getBlob($sourceContainer, $sourceBlob)->content->getContents(),
                    $this->client->getBlob($targetContainer, $targetBlob)->content->getContents()
                );
            });
        });
    }

    #[Test]
    public function throws_when_source_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->withContainer(__METHOD__, function (string $targetContainer) {
            $this->client->copyBlob("noop", "noop", $targetContainer, "copy");
        });
    }

    #[Test]
    public function throws_when_source_blob_doesnt_exist(): void
    {
        $this->expectException(BlobNotFoundException::class);

        $this->withContainer(__METHOD__, function (string $sourceContainer) {
            $this->withContainer(__METHOD__, function (string $targetContainer) use ($sourceContainer) {
                $this->client->copyBlob($sourceContainer, "noop", $targetContainer, "copy");
            });
        });
    }

    #[Test]
    public function throws_when_target_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->withBlob(__METHOD__, function (string $sourceContainer, string $sourceBlob) {
            $this->client->copyBlob($sourceContainer, $sourceBlob, "noop", "copy");
        });
    }
}
