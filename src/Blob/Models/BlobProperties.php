<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

use AzureOss\Storage\Blob\Exceptions\DateMalformedStringException;
use AzureOss\Storage\Blob\Helpers\MetadataHelper;
use Psr\Http\Message\ResponseInterface;

final class BlobProperties
{
    /**
     * @param array<string, string> $metadata
     */
    private function __construct(
        public readonly \DateTimeInterface $lastModified,
        public readonly int $contentLength,
        public readonly string $contentType,
        public readonly string $contentMD5,
        public readonly array $metadata,
    ) {}

    public static function fromResponseHeaders(ResponseInterface $response): self
    {
        $lastModified = \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC1123, $response->getHeaderLine('Last-Modified'));
        if ($lastModified === false) {
            throw new DateMalformedStringException("Azure returned a malformed date.");
        }

        return new BlobProperties(
            $lastModified,
            (int) $response->getHeaderLine('Content-Length'),
            $response->getHeaderLine('Content-Type'),
            $response->getHeaderLine('Content-MD5'),
            MetadataHelper::headersToMetadata($response->getHeaders()),
        );
    }

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $lastModified = \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC1123, (string) $xml->{'Last-Modified'});
        if ($lastModified === false) {
            throw new DateMalformedStringException("Azure returned a malformed date.");
        }

        return new self(
            $lastModified,
            (int) $xml->{'Content-Length'},
            (string) $xml->{'Content-Type'},
            (string) $xml->{'Content-MD5'},
            [],
        );
    }
}
