<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class DeleteContainerTestBlob extends BlobFeatureTestCase
{
    #[Test]
    public function container_is_deleted(): void
    {
        $this->withContainer(__METHOD__, function (string $container) {
            $this->assertTrue($this->client->containerExists($container));

            $this->client->deleteContainer($container);

            $this->assertFalse($this->client->containerExists($container));
        });
    }

    #[Test]
    public function throws_error_when_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->client->deleteContainer('noop');
    }
}
