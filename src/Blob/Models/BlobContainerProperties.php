<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

use AzureOss\Storage\Blob\Exceptions\DateMalformedStringException;
use AzureOss\Storage\Blob\Helpers\MetadataHelper;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use Psr\Http\Message\ResponseInterface;

final class BlobContainerProperties
{
    /**
     * @param array<string, string> $metadata
     */
    public function __construct(
        #[SerializedName('Last-Modified')]
        #[Type("DateTimeImmutable<'" . \DateTimeInterface::RFC1123 . "'>")]
        public readonly \DateTimeInterface $lastModified,
        public readonly array $metadata,
    ) {}

    public static function fromResponseHeaders(ResponseInterface $response): self
    {
        $lastModified = \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC1123, $response->getHeaderLine('Last-Modified'));
        if ($lastModified === false) {
            throw new DateMalformedStringException("Azure returned a malformed date.");
        }

        return new self($lastModified, MetadataHelper::headersToMetadata($response->getHeaders()));
    }

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $lastModified = \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC1123, (string) $xml->{'Last-Modified'});
        if ($lastModified === false) {
            throw new DateMalformedStringException("Azure returned a malformed date.");
        }

        return new self(
            $lastModified,
            []
        );
    }
}
