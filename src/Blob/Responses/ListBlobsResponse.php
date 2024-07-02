<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

class ListBlobsResponse
{
    /**
     * @param string $nextMarker
     * @param array<int, Blob|BlobPrefix> $blobs
     */
    private function __construct(
        public string $nextMarker,
        public array $blobs,
    ) {
    }

    public static function fromXml(array $parsed): ListBlobsResponse
    {
        $nextMarker = $parsed['NextMarker'];

        $blobs = [];
        foreach($parsed['Blobs'] as $key=> $value) {
            $blobs[] = match($key) {
                "Blob" => Blob::fromXml($value),
                "BlobPrefix" => BlobPrefix::fromXml($value),
            };
        }

        return new ListBlobsResponse($nextMarker, $blobs);
    }
}
