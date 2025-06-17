<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

use AzureOss\Storage\Blob\Helpers\DeprecationHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class BlobDownloadStreamingResult
{
    /**
     * @deprecated will be private in version 2
     */
    public function __construct(
        public readonly StreamInterface $content,
        public readonly BlobProperties $properties,
    ) {
        DeprecationHelper::constructorWillBePrivate(self::class, '2.0');
    }

    public static function fromResponse(ResponseInterface $response): self
    {
        /** @phpstan-ignore-next-line */
        return new self(
            $response->getBody(),
            BlobProperties::fromResponseHeaders($response),
        );
    }
}
