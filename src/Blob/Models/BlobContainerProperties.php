<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

use AzureOss\Storage\Blob\Exceptions\DateMalformedStringException;
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


        $metadata = [];

        foreach ($response->getHeaders() as $header => $values) {
            if (str_starts_with($header, "x-ms-meta-")) {
                $metadataKey = substr($header, strlen("x-ms-meta-"));
                $metadataValue = $response->getHeaderLine($header);

                $metadata[$metadataKey] = $metadataValue;
            }
        }

        return new self($lastModified, $metadata);
    }
}
