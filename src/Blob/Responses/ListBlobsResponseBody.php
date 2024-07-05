<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

use AzureOss\Storage\Blob\Models\Blob;
use AzureOss\Storage\Blob\Models\BlobPrefix;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\XmlList;

/**
 * @internal
 */
final class ListBlobsResponseBody
{
    /**
     * @param  Blob[]  $blobs
     * @param  BlobPrefix[]  $blobPrefixes
     */
    public function __construct(
        public readonly string $prefix,
        public readonly string $marker,
        public readonly int $maxResults,
        public readonly string $nextMarker,
        #[XmlList(entry: 'Blob')]
        public readonly array $blobs,
        #[SerializedName('Blobs')]
        #[XmlList(entry: 'BlobPrefix')]
        public readonly array $blobPrefixes,
        public readonly ?string $delimiter = null,
    ) {}
}
