<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

final class BlobHttpHeaders
{
    public function __construct(
        public ?string $cacheControl = null,
        public ?string $contentDisposition = null,
        public ?string $contentEncoding = null,
        public ?string $contentHash = null,
        public ?string $contentLanguage = null,
        public ?string $contentType = null,
    ) {}
}
