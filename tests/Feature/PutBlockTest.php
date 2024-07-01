<?php

declare(strict_types=1);

namespace Brecht\FlysystemAzureBlobStorage\Tests\Feature;

use Brecht\FlysystemAzureBlobStorage\Exceptions\ContainerNotFoundException;
use Brecht\FlysystemAzureBlobStorage\Requests\Block;
use Brecht\FlysystemAzureBlobStorage\Requests\BlockType;
use Brecht\FlysystemAzureBlobStorage\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class PutBlockTest extends FeatureTestCase
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
