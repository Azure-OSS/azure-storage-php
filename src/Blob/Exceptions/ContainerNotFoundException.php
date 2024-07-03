<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Exceptions;

final class ContainerNotFoundException extends \RuntimeException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('The specified container does not exist.', previous: $previous);
    }
}
