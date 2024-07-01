<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

class ListBlobsResponse
{
    public function __construct(
        public string $nextMarker,
        public BlobList $blobs,
    ) {
    }
}
