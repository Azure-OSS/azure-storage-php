<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

use Symfony\Component\Serializer\Annotation\SerializedName;

class BlobProperties
{
    public function __construct(
        #[SerializedName('Content-Length')]
        public string $contentLength,
        #[SerializedName('Content-Type')]
        public string $contentType,
        #[SerializedName('Content-MD5')]
        public string $contentMD5
    ) {
    }
}
