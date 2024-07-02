<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature\ContainerClient;

use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class ContainerExists extends BlobFeatureTestCase
{
    #[Test]
    public function checks_existence(): void
    {
        $container = substr(md5(__METHOD__), 0, 24);

        try {
            $this->serviceClient->getContainerClient($container)->delete();
        } catch (ContainerNotFoundException) {
            // do nothing
        }

        $this->assertFalse($this->serviceClient->getContainerClient($container)->exists());

        $this->serviceClient->getContainerClient($container)->create();

        $this->assertTrue($this->serviceClient->getContainerClient($container)->exists());

        $this->serviceClient->getContainerClient($container)->delete();

        $this->assertFalse($this->serviceClient->getContainerClient($container)->exists());
    }
}
