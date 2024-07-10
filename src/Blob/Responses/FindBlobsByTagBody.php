<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

use AzureOss\Storage\Blob\Models\TaggedBlob;
use JMS\Serializer\Annotation\XmlList;

/**
 * @internal
 */
final class FindBlobsByTagBody
{
    /**
     * @param string $nextMarker
     * @param TaggedBlob[] $blobs
     */
    public function __construct(
        public readonly string $nextMarker,
        #[XmlList(entry: 'Blob')]
        public readonly array $blobs,
    ) {}
}
