<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Options;

class PutBlockListOptions
{
    public function __construct(
        public ?string $contentType = null,
    )
    {
    }
}
