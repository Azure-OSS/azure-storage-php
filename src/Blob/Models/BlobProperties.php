<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

use AzureOss\Storage\Blob\Helpers\DateHelper;
use AzureOss\Storage\Blob\Helpers\DeprecationHelper;
use AzureOss\Storage\Blob\Helpers\HashHelper;
use AzureOss\Storage\Blob\Helpers\MetadataHelper;
use Psr\Http\Message\ResponseInterface;

final class BlobProperties
{
    /**
     * @deprecated will be private in version 2
     * @param array<string> $metadata
    */
    public function __construct(
        public readonly \DateTimeInterface $lastModified,
        public readonly int $contentLength,
        public readonly string $contentType,
        public readonly ?string $contentMD5,
        public readonly array $metadata,
        public readonly ?string $cacheControl = null,
        public readonly ?string $contentDisposition = null,
        public readonly ?string $contentLanguage = null,
        public readonly ?string $contentEncoding = null,
    ) {
        DeprecationHelper::constructorWillBePrivate(self::class, '2.0');
    }

    public static function fromResponseHeaders(ResponseInterface $response): self
    {
        return new BlobProperties(
            DateHelper::deserializeDateRfc1123Date($response->getHeaderLine('Last-Modified')),
            (int) ($response->getHeaderLine('Content-Length') ?: $response->getHeaderLine("x-encoded-content-length")),
            $response->getHeaderLine('Content-Type'),
            HashHelper::deserializeMd5($response->getHeaderLine('Content-MD5')),
            MetadataHelper::headersToMetadata($response->getHeaders()),
            $response->getHeaderLine('Cache-Control'),
            $response->getHeaderLine('Content-Disposition'),
            $response->getHeaderLine('Content-Language'),
            $response->getHeaderLine('x-encoded-content-encoding'),
        );
    }

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            DateHelper::deserializeDateRfc1123Date((string) $xml->{'Last-Modified'}),
            /** @phpstan-ignore-next-line */
            (int) $xml->{'Content-Length'},
            /** @phpstan-ignore-next-line */
            (string) $xml->{'Content-Type'},
            /** @phpstan-ignore-next-line */
            HashHelper::deserializeMd5((string) $xml->{'Content-MD5'}),
            [],
            null
        );
    }
}
