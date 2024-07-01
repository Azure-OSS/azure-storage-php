<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Feature;

use AzureOss\Storage\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class GetContainerPropertiesTest extends FeatureTestCase
{
    #[Test]
    public function returns_container_properties()
    {
        $this->expectNotToPerformAssertions();

        $this->withContainer(__METHOD__, function (string $container) {
            $this->client->getContainerProperties($container);
        });
    }

    #[Test]
    public function throws_when_container_does_not_exist()
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->client->getContainerProperties('noop');
    }
}
