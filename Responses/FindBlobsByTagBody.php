<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

use AzureOss\Storage\Blob\Models\TaggedBlob;
use SimpleXMLElement;

/**
 * @internal
 */
final class FindBlobsByTagBody
{
    /**
     * @param  TaggedBlob[]  $blobs
     */
    private function __construct(
        public readonly string $nextMarker,
        public readonly array $blobs,
    ) {}

    public static function fromXml(SimpleXMLElement $xml): self
    {
        $blobs = [];

        foreach ($xml->Blobs->children() as $blob) {
            $blobs[] = TaggedBlob::fromXml($blob);
        }

        return new self(
            (string) $xml->NextMarker,
            $blobs,
        );
    }
}
