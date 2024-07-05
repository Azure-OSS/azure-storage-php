<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Requests;

use JMS\Serializer\Annotation\XmlDiscriminator;
use JMS\Serializer\Annotation\XmlList;
use JMS\Serializer\Annotation\XmlRoot;

/**
 * @internal
 */
#[XmlRoot("BlockList")]
#[XmlDiscriminator(attribute: true)]
final class PutBlockRequestBody
{
    /**
     * @var string[]
     */
    #[XmlList(entry: "Committed", inline: true)]
    public array $committed;

    /**
     * @var string[]
     */
    #[XmlList(entry: "Uncommitted", inline: true)]
    public array $uncommitted;

    /**
     * @var string[]
     */
    #[XmlList(entry: "Latest", inline: true)]
    public array $latest;

    /**
     * @param Block[] $blocks
     */
    public function __construct(
        array $blocks,
    ) {
        foreach($blocks as $block) {
            $id = base64_encode($block->id);

            switch ($block->type) {
                case BlockType::COMMITTED:
                    $this->committed[] = $id;
                    break;
                case BlockType::UNCOMMITTED:
                    $this->uncommitted[] = $id;
                    break;
                case BlockType::LATEST:
                    $this->latest[] = $id;
            }
        }
    }
}
