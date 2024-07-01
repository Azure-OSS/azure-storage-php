<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Requests;

class ListBlobsOptions
{
    public function __construct(
        public ?string $prefix = null,
        public ?string $marker = null,
        public ?int $maxResults = null,
        public ?string $delimiter = null
    ) {
    }
}
