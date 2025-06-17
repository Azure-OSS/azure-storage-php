<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Auth;

interface TokenCredential
{
    public function getToken(): AccessToken;
}
