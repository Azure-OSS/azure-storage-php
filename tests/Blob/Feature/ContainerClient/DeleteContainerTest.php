<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature\ContainerClient;

use AzureOss\Storage\Blob\Clients\BlobContainerClient;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class DeleteContainerTest extends BlobFeatureTestCase
{
    #[Test]
    public function container_is_deleted(): void
    {
        $this->withContainer(__METHOD__, function (BlobContainerClient $containerClient) {
            $this->assertTrue($containerClient->exists());

            $containerClient->delete();

            $this->assertFalse($containerClient->exists());
        });
    }

    #[Test]
    public function throws_error_when_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient('noop')->delete();
    }
}
