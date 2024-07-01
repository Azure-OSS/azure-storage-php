<?php

declare(strict_types=1);

namespace Brecht\FlysystemAzureBlobStorage\Interfaces;

use Psr\Http\Message\RequestInterface;

interface AuthScheme
{
    public function computeAuthorizationHeader(RequestInterface $request): string;
}
