<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Options;

class UploadBlockBlobOptions
{
    public function __construct(
        public ?string $contentType = null,
    ) {
    }
}
