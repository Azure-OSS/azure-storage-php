<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Requests;

/**
 * @internal
 */
final class BlobTagsBody
{
    /**
     * @param  array<string>  $tags
     */
    public function __construct(
        public readonly array $tags,
    ) {}

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $tags = [];

        foreach ($xml->TagSet->children() as $tag) {
            $tags[(string) $tag->Key] = (string) $tag->Value;
        }

        return new self($tags);
    }

    public function toXml(): \SimpleXMLElement
    {
        $xml = new \SimpleXMLElement('<Tags></Tags>');
        $tagSet = $xml->addChild('TagSet');

        foreach ($this->tags as $key => $value) {
            $tag = $tagSet->addChild('Tag');
            $tag->addChild('Key', $key);
            $tag->addChild('Value', $value);
        }

        return $xml;
    }
}
