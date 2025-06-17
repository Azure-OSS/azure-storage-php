<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Auth;

final class AccessToken
{
    public function __construct(
        public readonly string $accessToken,
        public readonly \DateTimeInterface $expiresOn,
    ) {}
}
