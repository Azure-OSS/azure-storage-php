<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

use AzureOss\Storage\Blob\Helpers\DateHelper;
use AzureOss\Storage\Blob\Helpers\MetadataHelper;
use Psr\Http\Message\ResponseInterface;

final class BlobProperties
{
    /**
     * @param array<string> $metadata
     */
    public function __construct(
        public readonly \DateTimeInterface $lastModified,
        public readonly int $contentLength,
        public readonly string $contentType,
        public readonly ?string $contentMD5,
        public readonly array $metadata,
    ) {}

    public static function fromResponseHeaders(ResponseInterface $response): self
    {
        return new BlobProperties(
            DateHelper::deserializeDateRfc1123Date($response->getHeaderLine('Last-Modified')),
            (int) $response->getHeaderLine('Content-Length'),
            $response->getHeaderLine('Content-Type'),
            self::deserializeContentMD5($response->getHeaderLine('Content-MD5')),
            MetadataHelper::headersToMetadata($response->getHeaders()),
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
            self::deserializeContentMD5((string) $xml->{'Content-MD5'}),
            [],
        );
    }

    public static function deserializeContentMD5(string $contentMD5): ?string
    {
        $result = base64_decode($contentMD5, true);
        if ($result === false) {
            return null;
        }

        return bin2hex($result);
    }
}
