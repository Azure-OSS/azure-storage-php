<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Blob\Exceptions\InvalidBlockListException;
use AzureOss\Storage\Blob\Requests\Block;
use AzureOss\Storage\Blob\Requests\BlockType;
use AzureOss\Storage\Blob\Requests\PutBlockListOptions;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class PutBlockListTestBlob extends BlobFeatureTestCase
{
    #[Test]
    public function commits_block_list(): void
    {
        $this->withContainer(__METHOD__, function (string $container) {
            $blob = md5(__METHOD__);
            $blockA = new Block('ABCDEF', BlockType::UNCOMMITTED);
            $blockB = new Block('GHIJKL', BlockType::UNCOMMITTED);
            $blockC = new Block('MNOPQR', BlockType::UNCOMMITTED);

            $this->client->putBlock($container, $blob, $blockA, 'Lorem');
            $this->client->putBlock($container, $blob, $blockB, 'ipsum');
            $this->client->putBlock($container, $blob, $blockC, 'dolor');

            $this->client->putBlockList($container, $blob, [$blockA, $blockB, $blockC], new PutBlockListOptions('application/pdf'));
            $blobProps = $this->client->getBlobProperties($container, $blob);

            $this->assertEquals('application/pdf', $blobProps->contentType);
        });
    }

    #[Test]
    public function throws_when_block_list_is_invalid(): void
    {
        $this->expectException(InvalidBlockListException::class);

        $this->withContainer(__METHOD__, function (string $container) {
            $blob = md5(__METHOD__);

            $this->client->putBlockList($container, $blob, [new Block('ABCDEF', BlockType::UNCOMMITTED)]);
        });
    }

    #[Test]
    public function throws_when_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->client->putBlockList('noop', 'noop', []);
    }
}
