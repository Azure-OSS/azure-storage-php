<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

class BlobContainer
{
    public function __construct(
        public readonly string $name,
        public readonly BlobContainerProperties $properties,
    ) {}
}
