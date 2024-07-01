<?php

declare(strict_types=1);

namespace AzureOss\Storage\Exceptions;

class AuthorizationFailedException extends \RuntimeException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('Server failed to authenticate the request. Make sure the value of the Authorization header is formed correctly including the signature.', previous: $previous);
    }
}
