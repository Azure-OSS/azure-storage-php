<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Options;

final class PutBlobOptions
{
    public function __construct(
        public ?string $contentType = null,
    ) {
    }
}
