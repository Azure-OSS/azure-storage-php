<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

final class BlockBlobCommitBlockListOptions
{
    public function __construct(
        public readonly ?string $contentType = null,
        public readonly ?string $contentMD5 = null,
    ) {}
}
