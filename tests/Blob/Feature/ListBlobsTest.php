<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Blob\Requests\ListBlobsOptions;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class ListBlobsTest extends BlobFeatureTestCase
{
    #[Test]
    public function gets_blobs(): void
    {
        $this->withContainer(__METHOD__, function (string $container) {
            $this->client->uploadBlockBlob($container, "blobA", "lorem");
            $this->client->uploadBlockBlob($container, "blobB", "lorem");
            $this->client->uploadBlockBlob($container, "blobC", "lorem");
            $response = $this->client->listBlobs($container);

            $this->assertCount(3, $response->blobs);
        });
    }

    #[Test]
    public function gets_blobs_with_delimiter_and_prefix(): void
    {
        $this->withContainer(__METHOD__, function (string $container) {
            $this->client->uploadBlockBlob($container, "blobA", "lorem");
            $this->client->uploadBlockBlob($container, "folder/blobB", "lorem");
            $this->client->uploadBlockBlob($container, "folder/blobC", "lorem");
            $this->client->uploadBlockBlob($container, "folder/nestedA/blobD", "lorem");

            $response = $this->client->listBlobs($container, new ListBlobsOptions(
                prefix: "folder/",
                delimiter: "/"
            ));

            $this->assertCount(2, $response->blobs);
            $this->assertCount(1, $response->blobPrefixes);
        });
    }

    #[Test]
    public function throws_exception_when_container_already_exists(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->client->listBlobs('noop');
    }
}
