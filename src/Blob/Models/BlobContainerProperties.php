<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

use AzureOss\Storage\Blob\Exceptions\DateMalformedStringException;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use Psr\Http\Message\ResponseInterface;

final class BlobContainerProperties
{
    public function __construct(
        #[SerializedName('Last-Modified')]
        #[Type("DateTimeImmutable<'" . \DateTimeInterface::RFC1123 . "'>")]
        public readonly \DateTimeInterface $lastModified,
    ) {}

    public static function fromResponseHeaders(ResponseInterface $response): self
    {
        $lastModified = \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC1123, $response->getHeaderLine('Last-Modified'));
        if ($lastModified === false) {
            throw new DateMalformedStringException("Azure returned a malformed date.");
        }

        return new self($lastModified);
    }
}
