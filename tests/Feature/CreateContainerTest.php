<?php

declare(strict_types=1);

namespace Brecht\FlysystemAzureBlobStorage\Tests\Feature;

use Brecht\FlysystemAzureBlobStorage\Exceptions\ContainerAlreadyExistsException;
use Brecht\FlysystemAzureBlobStorage\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class CreateContainerTest extends FeatureTestCase
{
    #[Test]
    public function container_is_created(): void
    {
        $this->withContainer(__METHOD__, function (string $container) {
            $this->assertTrue($this->client->containerExists($container));
        });
    }

    #[Test]
    public function throws_exception_when_container_already_exists()
    {
        $this->expectException(ContainerAlreadyExistsException::class);

        $this->withContainer(__METHOD__, function (string $container) {
            $this->client->createContainer($container);
        });
    }
}
