<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Feature;

use AzureOss\Storage\Exceptions\BlobNotFoundException;
use AzureOss\Storage\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Requests\PutBlobOptions;
use AzureOss\Storage\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class PutBlobTest extends FeatureTestCase
{
    #[Test]
    public function blob_is_created()
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
    public function throws_when_container_is_not_found()
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->client->putBlob('noop', 'noop', 'Lorem');
    }
}
