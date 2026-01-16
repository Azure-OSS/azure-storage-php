<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

use AzureOss\Storage\Blob\Models\BlobContainer;

/**
 * @internal
 */
final class ListContainersResponseBody
{
    /**
     * @param  BlobContainer[]  $containers
     */
    private function __construct(
        public readonly string $prefix,
        public readonly string $marker,
        public readonly int $maxResults,
        public readonly string $nextMarker,
        public readonly array $containers,
    ) {}

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $containers = [];
        foreach ($xml->Containers->children() as $container) {
            $containers[] = BlobContainer::fromXml($container);
        }

        return new self(
            (string) $xml->Prefix,
            (string) $xml->Marker,
            (int) $xml->MaxResults,
            (string) $xml->NextMarker,
            $containers,
        );
    }
}
