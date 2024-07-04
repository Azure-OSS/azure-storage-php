<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\SAS;

use AzureOss\Storage\Common\SAS\SASIpRange;
use AzureOss\Storage\Common\SAS\SASProtocol;

class BlobSASSignatureValues
{
    public function __construct(
        public readonly string $containerName,
        public readonly \DateTimeInterface $expiresOn,
        public readonly ?string $blobName = null,
        public readonly ?string $permissions = null,
        public readonly ?string $identifier = null,
        public readonly ?\DateTimeInterface $startsOn = null,
        public readonly ?string $cacheControl = null,
        public readonly ?string $contentDisposition = null,
        public readonly ?string $contentEncoding = null,
        public readonly ?string $contentLanguage = null,
        public readonly ?string $contentType = null,
        public readonly ?string $encryptionScope = null,
        public readonly ?SASIpRange $ipRange = null,
        public readonly ?\DateTimeInterface $snapshotTime = null,
        public readonly ?SASProtocol $protocol = null,
        public readonly ?string $version = null,
    ) {
        if ($this->permissions === null && $this->identifier === null) {
            throw new \InvalidArgumentException('Permissions or identifier is required.');
        }
    }
}
