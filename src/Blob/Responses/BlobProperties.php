<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

class BlobProperties
{
    #[SerializedName('Last-Modified')]
    #[Type("DateTimeImmutable<'" . \DateTimeInterface::RFC1123 ."'>")]
    public readonly \DateTimeInterface $lastModified;

    #[SerializedName('Content-Length')]
    public readonly int $contentLength;

    #[SerializedName('Content-Type')]
    public readonly string $contentType;
}
