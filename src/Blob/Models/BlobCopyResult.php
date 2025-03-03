<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

use Psr\Http\Message\ResponseInterface;

final class BlobCopyResult
{
    private function __construct(
        public readonly string $copyId,
        public readonly CopyStatus $copyStatus,
    ) {}

    public static function fromResponse(ResponseInterface $response): self
    {
        return new self(
            $response->getHeaderLine('x-ms-copy-id'),
            CopyStatus::from($response->getHeaderLine('x-ms-copy-status')),
        );
    }
}
