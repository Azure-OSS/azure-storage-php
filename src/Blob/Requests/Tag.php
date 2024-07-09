<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Requests;

/**
 * @internal
 */
final class Tag
{
    public function __construct(
        public readonly string $key,
        public readonly string $value,
    ) {}
}
