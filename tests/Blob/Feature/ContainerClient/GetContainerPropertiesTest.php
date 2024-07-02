<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature\ContainerClient;

use AzureOss\Storage\Blob\ContainerClient;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class GetContainerPropertiesTest extends BlobFeatureTestCase
{
    #[Test]
    public function returns_container_properties(): void
    {
        $this->expectNotToPerformAssertions();

        $this->withContainer(__METHOD__, function (ContainerClient $containerClient) {
            $containerClient->getProperties();
        });
    }

    #[Test]
    public function throws_when_container_does_not_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient('noop')->getProperties();
    }
}
