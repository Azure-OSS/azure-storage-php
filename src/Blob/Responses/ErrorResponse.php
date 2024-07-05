<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

/**
 * @internal
 */
class ErrorResponse
{
    public function __construct(
        public readonly string $code,
        public readonly string $message,
    ) {}
}
