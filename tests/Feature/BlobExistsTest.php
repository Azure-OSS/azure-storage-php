<?php

declare(strict_types=1);

namespace Brecht\FlysystemAzureBlobStorage\Tests\Feature;

use Brecht\FlysystemAzureBlobStorage\Exceptions\ContainerNotFoundException;
use Brecht\FlysystemAzureBlobStorage\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class BlobExistsTest extends FeatureTestCase
{
    #[Test]
    public function checks_existence(): void
    {
        $this->withContainer(__METHOD__, function (string $container) {
            $this->assertFalse($this->client->blobExists($container, 'noop'));
        });

        $this->withBlob(__METHOD__, function (string $container, string $blob) {
            $this->assertTrue($this->client->blobExists($container, $blob));
        });
    }

    #[Test]
    public function throws_when_container_does_not_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->client->deleteBlob('noop', 'noop');
    }
}
