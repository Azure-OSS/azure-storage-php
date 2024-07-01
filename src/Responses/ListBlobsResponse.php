<?php

declare(strict_types=1);

namespace Brecht\FlysystemAzureBlobStorage\Responses;

 class ListBlobsResponse
{
    public function __construct(
        public string $nextMarker,
        public BlobList $blobs,
    ) {
    }
}
