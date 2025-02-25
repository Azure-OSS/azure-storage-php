<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class BlobDownloadStreamingResult
{
    public function __construct(
        public readonly StreamInterface $content,
        public readonly BlobProperties $properties,
    ) {}

    public static function fromResponse(ResponseInterface $response): self
    {
        return new self(
            $response->getBody(),
            BlobProperties::fromResponseHeaders($response),
        );
    }
}
