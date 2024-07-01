<?php

declare(strict_types=1);

namespace AzureOss\Storage\Responses;

 class Blob
{
    public function __construct(
        public string $name,
        public BlobProperties $properties,
    ) {
    }
}
