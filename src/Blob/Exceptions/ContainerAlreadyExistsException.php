<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Exceptions;

final class ContainerAlreadyExistsException extends \RuntimeException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('The specified container already exists.', previous: $previous);
    }
}
