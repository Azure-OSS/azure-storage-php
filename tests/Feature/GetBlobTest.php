<?php

declare(strict_types=1);

namespace Brecht\FlysystemAzureBlobStorage\Tests\Feature;

use Brecht\FlysystemAzureBlobStorage\Exceptions\BlobNotFoundException;
use Brecht\FlysystemAzureBlobStorage\Exceptions\ContainerNotFoundException;
use Brecht\FlysystemAzureBlobStorage\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class GetBlobTest extends FeatureTestCase
{
    #[Test]
    public function gets_blob(): void
    {
        $this->expectNotToPerformAssertions();

        $this->withBlob(__METHOD__, function (string $containers, string $blob) {
            $this->client->getBlob($containers, $blob);
        });
    }

    #[Test]
    public function throws_when_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->client->getBlob('noop', 'noop');
    }

    #[Test]
    public function throws_when_blob_doesnt_exist(): void
    {
        $this->expectException(BlobNotFoundException::class);

        $this->withContainer(__METHOD__, function (string $container) {
            $this->client->getBlob($container, 'noop');
        });
    }
}
