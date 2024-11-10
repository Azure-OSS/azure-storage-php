<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Requests;

/**
 * @internal
 */
final class PutBlockRequestBody
{
    /**
     * @param Block[] $blocks
     */
    public function __construct(
        public array $blocks,
    ) {}

    public function toXml(): \SimpleXMLElement
    {
        $xml = new \SimpleXMLElement("<BlockList></BlockList>");

        foreach ($this->blocks as $block) {
            $xml->addChild($block->type->value, $block->getId());
        }

        return $xml;
    }
}
