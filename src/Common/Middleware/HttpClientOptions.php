<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Middleware;

final class HttpClientOptions
{
    public function __construct(
        public readonly ?int $timeout = null,
        public readonly ?int $connectTimeout = null,
    ) {}

    /**
     * @return array{timeout?: int, connect_timeout?: int}
     */
    public function toGuzzleHttpClientConfig(): array
    {
        return array_filter([
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
        ], fn($value) => $value !== null);
    }
}
