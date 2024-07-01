<?php

declare(strict_types=1);

namespace AzureOss\Storage\Exceptions;

class BlobNotFoundException extends \RuntimeException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('The specified blob does not exist.', previous: $previous);
    }
}
