<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common;

use Psr\Http\Message\RequestInterface;

interface AuthScheme
{
    public function signRequest(RequestInterface $request): RequestInterface;
}
