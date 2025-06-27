<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

/**
 * Represents HTTP headers that can be applied to a Blob in a storage system.
 *
 * The headers define additional metadata or control options, including but not
 * limited to content type, caching policy, content encoding, content language,
 * and content disposition.
 *
 * Each property can be set to a specific value or left as null if not applicable.
 */
final class BlobHttpHeaders
{
    public function __construct(
        public ?string $contentType = null,
        public ?string $cacheControl = null,
        public ?string $contentEncoding = null,
        public ?string $contentLanguage = null,
        public ?string $contentDisposition = null,
        public ?string $contentHash = null,
    ) {}
}
