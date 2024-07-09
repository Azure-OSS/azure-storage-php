<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

use AzureOss\Storage\Blob\Models\BlobContainer;
use JMS\Serializer\Annotation\XmlList;

/**
 * @internal
 */
class ListContainersResponseBody
{
    /**
     * @param BlobContainer[]  $containers
     */
    public function __construct(
        public readonly string $prefix,
        public readonly string $marker,
        public readonly int $maxResults,
        public readonly string $nextMarker,
        #[XmlList(entry: 'Container')]
        public readonly array $containers,
    ) {}
}
