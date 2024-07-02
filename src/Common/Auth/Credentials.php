<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Auth;

use Psr\Http\Message\RequestInterface;

interface Credentials
{
    public function computeAuthorizationHeader(RequestInterface $request): string;
}
