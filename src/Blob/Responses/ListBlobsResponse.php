<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

use AzureOss\Storage\Common\Utils\Xml;

final class ListBlobsResponse implements XmlDecodable
{
    /**
     * @param string $nextMarker
     * @param Blob[] $blobs
     * @param BlobPrefix[] $blobPrefixes,
     */
    private function __construct(
        public string $nextMarker,
        public array $blobs,
        public array $blobPrefixes,
    ) {
    }

    public static function fromXml(array $parsed): static
    {
        $nextMarker = Xml::str($parsed, 'NextMarker');

        $blobs = [];
        foreach (Xml::list($parsed, 'Blobs.Blob') as $blob) {
            $blobs[] = Blob::fromXml($blob);
        }

        $blobPrefixes = [];
        foreach (Xml::list($parsed, 'Blobs.BlobPrefix') as $blobPrefix) {
            $blobPrefixes[] = BlobPrefix::fromXml($blobPrefix);
        }

        return new self($nextMarker, $blobs, $blobPrefixes);
    }
}
