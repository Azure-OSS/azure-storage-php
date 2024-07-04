<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature\BlockBlobClient;

use AzureOss\Storage\Blob\Clients\ContainerClient;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Blob\Exceptions\InvalidBlockListException;
use AzureOss\Storage\Blob\Options\Block;
use AzureOss\Storage\Blob\Options\BlockType;
use AzureOss\Storage\Blob\Options\PutBlockListOptions;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class PutBlockListTest extends BlobFeatureTestCase
{
    #[Test]
    public function commits_block_list(): void
    {
        $this->withContainer(__METHOD__, function (ContainerClient $containerClient) {
            $blob = md5(__METHOD__);
            $blockA = new Block('ABCDEF', BlockType::UNCOMMITTED);
            $blockB = new Block('GHIJKL', BlockType::UNCOMMITTED);
            $blockC = new Block('MNOPQR', BlockType::UNCOMMITTED);

            $blockClient = $containerClient->getBlockBlobClient($blob);

            $blockClient->putBlock($blockA, 'Lorem ');
            $blockClient->putBlock($blockB, 'ipsum ');
            $blockClient->putBlock($blockC, 'dolor');

            $blockClient->putBlockList([$blockA, $blockB, $blockC], new PutBlockListOptions('application/pdf'));
            $blobResponse = $blockClient->getBlobClient()->get();

            $this->assertEquals('application/pdf', $blobResponse->contentType);
            $this->assertEquals('Lorem ipsum dolor', $blobResponse->content->getContents());
        });
    }

    #[Test]
    public function throws_when_block_list_is_invalid(): void
    {
        $this->expectException(InvalidBlockListException::class);

        $this->withContainer(__METHOD__, function (ContainerClient $containerClient) {
            $containerClient->getBlockBlobClient("test")->putBlockList([new Block('ABCDEF', BlockType::UNCOMMITTED)]);
        });
    }

    #[Test]
    public function throws_when_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient('noop')->getBlockBlobClient('noop')->putBlockList([]);
    }
}
