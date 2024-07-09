<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Requests;

use JMS\Serializer\Annotation\XmlList;
use JMS\Serializer\Annotation\XmlRoot;

/**
 * @internal
 */
#[XmlRoot("Tags")]
final class BlobTagsBody
{
    /**
     * @param Tag[] $tagSet
     */
    private function __construct(
        #[XmlList(entry: 'Tag')]
        public readonly array $tagSet,
    ) {}

    /**
     * @param array<string, string> $tags
     */
    public static function fromArray(array $tags): self
    {
        $tagSet = [];
        foreach ($tags as $key => $value) {
            $tagSet[] =  new Tag($key, $value);
        }

        return new self($tagSet);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $tags = [];

        foreach ($this->tagSet as $tag) {
            $tags[$tag->key] = $tag->value;
        }

        return $tags;
    }
}
