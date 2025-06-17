<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

use AzureOss\Storage\Blob\Helpers\DateHelper;
use AzureOss\Storage\Blob\Helpers\DeprecationHelper;
use AzureOss\Storage\Blob\Helpers\HashHelper;
use AzureOss\Storage\Blob\Helpers\MetadataHelper;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

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
        public readonly ?string $copyId = null,
        public readonly ?UriInterface $copySource = null,
        public readonly ?CopyStatus $copyStatus = null,
        public readonly ?string $copyStatusDescription = null,
        public readonly ?\DateTimeInterface $copyCompletionTime = null,
    ) {
        DeprecationHelper::constructorWillBePrivate(self::class, '2.0');
    }

    public static function fromResponseHeaders(ResponseInterface $response): self
    {
        /** @phpstan-ignore-next-line */
        return new BlobProperties(
            DateHelper::deserializeDateRfc1123Date($response->getHeaderLine('Last-Modified')),
            (int) $response->getHeaderLine('Content-Length'),
            $response->getHeaderLine('Content-Type'),
            HashHelper::deserializeMd5($response->getHeaderLine('Content-MD5')),
            MetadataHelper::headersToMetadata($response->getHeaders()),
            $response->hasHeader('x-ms-copy-id') ? $response->getHeaderLine('x-ms-copy-id') : null,
            $response->hasHeader('x-ms-copy-source') ? new Uri($response->getHeaderLine('x-ms-copy-source')) : null,
            $response->hasHeader('x-ms-copy-status') ? CopyStatus::from($response->getHeaderLine('x-ms-copy-status')) : null,
            $response->hasHeader('x-ms-copy-status-description') ? $response->getHeaderLine('x-ms-copy-status-description') : null,
            $response->hasHeader('x-ms-copy-completion-time') ? DateHelper::deserializeDateRfc1123Date($response->getHeaderLine('x-ms-copy-completion-time')) : null,
        );
    }

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        /** @phpstan-ignore-next-line */
        return new self(
            DateHelper::deserializeDateRfc1123Date((string) $xml->{'Last-Modified'}),
            (int) $xml->{'Content-Length'},
            (string) $xml->{'Content-Type'},
            HashHelper::deserializeMd5((string) $xml->{'Content-MD5'}),
            [],
        );
    }
}
