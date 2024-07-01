<?php

declare(strict_types=1);

namespace AzureOss\Storage\Requests;

class UploadBlockBlobOptions
{
    public function __construct(
        public ?string $contentType = null
    ) {
    }
}
