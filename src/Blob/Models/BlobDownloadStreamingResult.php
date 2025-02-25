<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

use Psr\Http\Message\StreamInterface;

final class BlobDownloadStreamingResult
{
    /**
     * @deprecated will be private in version 2
     */
    public function __construct(
        public readonly StreamInterface $content,
        public readonly BlobProperties $properties,
    ) {}
}
