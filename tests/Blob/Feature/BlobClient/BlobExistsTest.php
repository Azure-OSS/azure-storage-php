<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature\BlobClient;

use AzureOss\Storage\Blob\Clients\BlobClient;
use AzureOss\Storage\Blob\Clients\BlobContainerClient;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class BlobExistsTest extends BlobFeatureTestCase
{
    #[Test]
    public function checks_existence(): void
    {
        $this->withContainer(__METHOD__, function (BlobContainerClient $client) {
            $this->assertFalse($client->getBlobClient('noop')->exists());
        });

        $this->withBlob(__METHOD__, function (BlobClient $client) {
            $this->assertTrue($client->exists());
        });
    }

    #[Test]
    public function throws_when_container_does_not_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient('noop')->getBlobClient('noop')->exists();
    }
}
