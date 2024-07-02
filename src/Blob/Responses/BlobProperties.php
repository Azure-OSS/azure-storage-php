<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

class BlobProperties
{
    #[SerializedName('Last-Modified')]
    #[Type("DateTimeImmutable<'" . \DateTimeInterface::RFC1123 ."'>")]
    public \DateTimeInterface $lastModified;

    #[SerializedName('Content-Length')]
    public int $contentLength;

    #[SerializedName('Content-Type')]
    public string $contentType;
}
