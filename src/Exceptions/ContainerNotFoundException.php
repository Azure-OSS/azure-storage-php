<?php

declare(strict_types=1);

namespace Brecht\FlysystemAzureBlobStorage\Exceptions;

class ContainerNotFoundException extends \RuntimeException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('The specified container does not exist.', previous: $previous);
    }
}
