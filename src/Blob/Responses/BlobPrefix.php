<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

final class BlobPrefix
{
    public function __construct(
        public readonly string $name,
    ) {}
}
