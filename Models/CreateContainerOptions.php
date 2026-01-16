<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

final class CreateContainerOptions
{
    public function __construct(
        public PublicAccessType $publicAccessType = PublicAccessType::NONE,
    ) {}
}
