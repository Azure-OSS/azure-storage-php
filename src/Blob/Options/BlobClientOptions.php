<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Options;

use AzureOss\Storage\Common\Middleware\HttpClientOptions;

final class BlobClientOptions
{
    public function __construct(
        public readonly HttpClientOptions $httpClientOptions = new HttpClientOptions(),
    ) {}
}
