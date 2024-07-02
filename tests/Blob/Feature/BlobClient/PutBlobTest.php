<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature\BlobClient;

use AzureOss\Storage\Blob\ContainerClient;
use AzureOss\Storage\Blob\Exceptions\BlobNotFoundException;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Blob\Requests\PutBlobOptions;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class PutBlobTest extends BlobFeatureTestCase
{
    #[Test]
    public function blob_is_created(): void
    {
        $this->withContainer(__METHOD__, function (ContainerClient $containerClient) {

            $blobClient = $containerClient->getBlobClient("test");
            try {
                $blobClient->delete();
            } catch (BlobNotFoundException) {
                // do nothing
            }

            $blobClient->put('Lorem', new PutBlobOptions(contentType: 'application/pdf'));

            $blobResponse = $blobClient->get();

            $this->assertEquals('application/pdf', $blobResponse->contentType);
            $this->assertEquals('Lorem', $blobResponse->content->getContents());
        });
    }

    #[Test]
    public function throws_when_container_is_not_found(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient('noop')->getBlobClient('noop')->put('Lorem');
    }
}
