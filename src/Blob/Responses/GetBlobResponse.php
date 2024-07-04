<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

use Psr\Http\Message\StreamInterface;

final class GetBlobResponse
{
    public function __construct(
        public StreamInterface $content,
        public \DateTimeInterface $lastModified,
        public int $contentLength,
        public string $contentType,
    ) {}
}
