<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

class Blob
{
    public function __construct(
        public readonly string $name,
        public readonly BlobProperties $properties,
    ) {
    }
}
