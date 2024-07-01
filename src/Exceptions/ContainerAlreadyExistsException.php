<?php

declare(strict_types=1);

namespace AzureOss\Storage\Exceptions;

class ContainerAlreadyExistsException extends \RuntimeException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('The specified container already exists.', previous: $previous);
    }
}
