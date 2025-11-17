<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Middleware;

final class HttpClientOptions
{
    public function __construct(
        public readonly ?int $timeout = null,
        public readonly ?int $connectTimeout = null,
        public readonly ?bool $verifySsl = null,
    ) {}

    /**
     * @return array{timeout?: int, connect_timeout?: int, verify?: bool}
     */
    public function toGuzzleHttpClientConfig(): array
    {
        return array_filter([
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'verify' => $this->verifySsl,
        ], fn($value) => $value !== null);
    }
}
