<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature\ContainerClient;

use AzureOss\Storage\Blob\ContainerClient;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Blob\Requests\ListBlobsOptions;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class ListBlobsTest extends BlobFeatureTestCase
{
    #[Test]
    public function gets_blobs(): void
    {
        $this->withContainer(__METHOD__, function (ContainerClient $containerClient) {
            $containerClient->getBlobClient("blobA")->put("lorem");
            $containerClient->getBlobClient("blobB")->put("lorem");
            $containerClient->getBlobClient("blobC")->put("lorem");
            $response = $containerClient->listBlobs();

            $this->assertCount(3, $response->blobs);
        });
    }

    #[Test]
    public function gets_blobs_with_delimiter_and_prefix(): void
    {
        $this->withContainer(__METHOD__, function (ContainerClient $containerClient) {
            $containerClient->getBlobClient("blobA")->put("lorem");
            $containerClient->getBlobClient("folder/blobB")->put("lorem");
            $containerClient->getBlobClient("folder/blobC")->put("lorem");
            $containerClient->getBlobClient("folder/nestedA/blobD")->put("lorem");

            $response = $containerClient->listBlobs(new ListBlobsOptions(
                prefix: "folder/",
                delimiter: "/"
            ));

            $this->assertCount(2, $response->blobs);
            $this->assertCount(1, $response->blobPrefixes);
        });
    }

    #[Test]
    public function throws_exception_when_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient('noop')->listBlobs();
    }
}
