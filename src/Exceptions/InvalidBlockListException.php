<?php

declare(strict_types=1);

namespace AzureOss\Storage\Exceptions;

class InvalidBlockListException extends \RuntimeException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('The specified block ID is invalid. The block ID must be Base64-encoded.', previous: $previous);
    }
}
