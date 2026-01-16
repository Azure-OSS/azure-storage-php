<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

final class BlobHttpHeaders
{
    public function __construct(
        public string $cacheControl = '',
        public string $contentDisposition = '',
        public string $contentEncoding = '',
        public string $contentHash = '',
        public string $contentLanguage = '',
        public string $contentType = '',
    ) {}

    /**
     * @internal
     *
     * @return array{
     *     x-ms-blob-cache-control?: string,
     *     x-ms-blob-content-type?: string,
     *     x-ms-blob-content-encoding?: string,
     *     x-ms-blob-content-language?: string,
     *     x-ms-blob-content-md5?: string,
     *     x-ms-blob-content-disposition?: string
     * }
     */
    public function toArray(): array
    {
        return array_filter([
            'x-ms-blob-cache-control' => $this->cacheControl,
            'x-ms-blob-content-type' => $this->contentType,
            'x-ms-blob-content-encoding' => $this->contentEncoding,
            'x-ms-blob-content-language' => $this->contentLanguage,
            'x-ms-blob-content-md5' => $this->contentHash !== '' ? base64_encode($this->contentHash) : '',
            'x-ms-blob-content-disposition' => $this->contentDisposition,
        ], fn ($value) => $value !== '');
    }
}
