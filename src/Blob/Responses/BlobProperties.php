<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

use AzureOss\Storage\Common\Utils\Xml;

final class BlobProperties implements XmlDecodable
{
    private function __construct(
//        public string $creationTime,
        public \DateTimeInterface $lastModified,
//        public string $etag,
//        public string $owner,
//        public string $group,
//        public string $permissions,
//        public string $acl,
//        public string $resourceType,
//        public string $placeholder,
        public int $contentLength,
        public string $contentType,
//        public string $contentEncoding,
//        public string $contentLanguage,
//        public string $contentMD5,
//        public string $cacheControl,
//        public string $blobSequenceNumber,
//        public string $blobType,
//        public string $accessTier,
//        public string $leaseStatus,
//        public string $leaseState,
//        public string $leaseDuration,
//        public string $copyId,
//        public string $copyStatus,
//        public string $copySource,
//        public string $copyProgress,
//        public string $copyCompletionTime,
//        public string $copyStatusDescription,
//        public string $serverEncrypted,
//        public string $customerProvidedKeySha256,
//        public string $encryptionContext,
//        public string $encryptionScope,
//        public string $incrementalCopy,
//        public string $accessTierInferred,
//        public string $accessTierChangeTime,
//        public string $deletedTime,
//        public string $remainingRetentionDays,
//        public string $tagCount,
//        public string $rehydratePriority,
//        public string $expiryTime
    )
    {
    }

    public static function fromXml(array $parsed): static
    {
        $lastModified = Xml::dateTime($parsed, 'Last-Modified', \DateTimeInterface::RFC1123);
        $contentLength = Xml::int($parsed, 'Content-Length');
        $contentType = Xml::str($parsed, 'Content-Type');

        return new self($lastModified, $contentLength, $contentType);
    }
}
