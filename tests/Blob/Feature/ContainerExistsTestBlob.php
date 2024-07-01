<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class ContainerExistsTestBlob extends BlobFeatureTestCase
{
    #[Test]
    public function checks_existence(): void
    {
        $container = substr(md5(__METHOD__), 0, 24);

        try {
            $this->client->deleteContainer($container);
        } catch (ContainerNotFoundException) {
            // do nothing
        }

        $this->assertFalse($this->client->containerExists($container));

        $this->client->createContainer($container);

        $this->assertTrue($this->client->containerExists($container));

        $this->client->deleteContainer($container);

        $this->assertFalse($this->client->containerExists($container));
    }
}
