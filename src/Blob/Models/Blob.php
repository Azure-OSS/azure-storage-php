<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

final class Blob
{
    public function __construct(
        public readonly string             $name,
        public readonly BlobItemProperties $properties,
    ) {}
}
