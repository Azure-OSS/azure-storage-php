<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

final class BlobProperties
{
    public function __construct(
        public \DateTimeInterface $lastModified,
        public int $contentLength,
        public string $contentType,
    ) {}
}
