<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

/**
 * @internal
 */
final class BlobPrefix
{
    public function __construct(
        public readonly string $name,
    ) {}
}
