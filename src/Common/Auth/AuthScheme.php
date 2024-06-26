<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Auth;

use Psr\Http\Message\RequestInterface;

interface AuthScheme
{
    public function computeAuthorizationHeader(RequestInterface $request): string;
}
