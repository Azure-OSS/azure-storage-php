<?php

declare(strict_types=1);

namespace AzureOss\Storage\Responses;

 class ListBlobsResponse
{
    public function __construct(
        public string $nextMarker,
        public BlobList $blobs,
    ) {
    }
}
