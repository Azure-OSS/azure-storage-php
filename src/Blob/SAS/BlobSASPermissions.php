<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\SAS;

class BlobSASPermissions
{
    public function __construct(
        public readonly bool $read,
    ) {}

    public function __toString(): string
    {
        return 'r';
    }
}
