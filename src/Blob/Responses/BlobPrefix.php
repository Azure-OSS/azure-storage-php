<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

class BlobPrefix
{
    public function __construct(
        public readonly string $name
    ) {
    }
}
