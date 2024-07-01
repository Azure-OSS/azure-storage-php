<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Feature;

use AzureOss\Storage\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class ContainerExistsTest extends FeatureTestCase
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
