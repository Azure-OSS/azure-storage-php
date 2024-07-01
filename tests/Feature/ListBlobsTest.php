<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Feature;

use AzureOss\Storage\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class ListBlobsTest extends FeatureTestCase
{
    #[Test]
    public function gets_blobs(): void
    {
        $this->expectNotToPerformAssertions();

        $this->withContainer(__METHOD__, function (string $container) {
            $this->client->listBlobs($container);
        });
    }

    #[Test]
    public function throws_exception_when_container_already_exists()
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->client->listBlobs('noop');
    }
}
