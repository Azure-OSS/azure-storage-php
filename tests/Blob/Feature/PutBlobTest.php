<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

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
        $this->withContainer(__METHOD__, function (string $container) {
            $blob = md5(__METHOD__);

            try {
                $this->client->deleteBlob($container, $blob);
            } catch (BlobNotFoundException) {
                // do nothing
            }

            $this->client->putBlob($container, $blob, 'Lorem', new PutBlobOptions(contentType: 'application/pdf'));

            $blobResponse = $this->client->getBlob($container, $blob);

            $this->assertEquals('application/pdf', $blobResponse->contentType);
        });
    }

    #[Test]
    public function throws_when_container_is_not_found(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->client->putBlob('noop', 'noop', 'Lorem');
    }
}
