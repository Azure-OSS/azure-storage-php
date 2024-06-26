<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

class GetBlobPropertiesResponse
{
    public function __construct(
        public \DateTimeInterface $lastModified,
        public int $contentLength,
        public string $contentType,
    ) {
    }
}
