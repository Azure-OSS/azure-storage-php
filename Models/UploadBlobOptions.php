<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

final class UploadBlobOptions
{
    public readonly BlobHttpHeaders $httpHeaders;

    /**
     * @param  int  $initialTransferSize  The size of the first range request in bytes. Blobs smaller than this limit will be transferred in a single request. Blobs larger than this limit will continue being transferred in chunks of size MaximumTransferSize.
     * @param  int  $maximumTransferSize  The maximum length of a transfer in bytes.
     * @param  int  $maximumConcurrency  The maximum number of workers that may be used in a parallel transfer.
     */
    public function __construct(
        public ?string $contentType = null,
        public int $initialTransferSize = 256_000_000,
        public int $maximumTransferSize = 8_000_000,
        public int $maximumConcurrency = 25,
        ?BlobHttpHeaders $httpHeaders = null,
    ) {
        $this->httpHeaders = $httpHeaders ?? new BlobHttpHeaders;

        if ($this->httpHeaders->contentType === '' && $contentType !== null) {
            $this->httpHeaders->contentType = $contentType;
        }
    }
}
