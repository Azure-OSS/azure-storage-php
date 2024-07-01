<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Blob\Requests\Block;
use AzureOss\Storage\Blob\Requests\BlockType;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class PutBlockTest extends BlobFeatureTestCase
{
    #[Test]
    public function commits_block_list(): void
    {
        $this->expectNotToPerformAssertions();

        $this->withContainer(__METHOD__, function (string $container) {
            $blob = md5(__METHOD__);
            $block = new Block('ABCDEF', BlockType::UNCOMMITTED);

            $this->client->putBlock($container, $blob, $block, 'Lorem');
        });
    }

    #[Test]
    public function throws_when_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->client->putBlockList('noop', 'noop', []);
    }
}
