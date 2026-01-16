<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

final class GetBlobsOptions
{
    public function __construct(
        public ?int $pageSize = null,
    ) {}
}
