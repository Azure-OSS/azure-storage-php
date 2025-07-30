<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

final class BlobHttpHeaders
{
    public function __construct(
        public string $cacheControl = "",
        public string $contentDisposition = "",
        public string $contentEncoding = "",
        public string $contentHash = "",
        public string $contentLanguage = "",
        public string $contentType = "",
    ) {}
}
