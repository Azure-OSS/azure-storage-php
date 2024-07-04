<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\SAS;

use AzureOss\Storage\Common\ApiVersion;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use GuzzleHttp\Psr7\Query;

final class BlobSASQueryParameters
{
    private function __construct(
        public string $signedVersion,
        public string $signedResource,
        public string $signedExpiry,
        public string $signature,
        public ?string $signedStart = null,
        public ?string $signedPermissions = null,
        public ?string $signedIdentifier = null,
        public ?string $signedIp = null,
        public ?string $cacheControl = null,
        public ?string $contentDisposition = null,
        public ?string $contentEncoding = null,
        public ?string $contentLanguage = null,
        public ?string $contentType = null,
        public ?string $signedProtocol = null,
        public ?string $signedSnapshotTime = null,
        public ?string $signedEncryptionScope = null,
    ) {}

    public static function generate(
        BlobSASSignatureValues     $blobSASSignatureValues,
        StorageSharedKeyCredential $sharedKeyCredential,
    ): self {
        $signedStart = $blobSASSignatureValues->startsOn?->format(\DateTimeInterface::ATOM);
        $signedExpiry = $blobSASSignatureValues->expiresOn->format(\DateTimeInterface::ATOM);
        $signedResource = $blobSASSignatureValues->blobName ? "b" : "c";
        $signedIp = $blobSASSignatureValues->ipRange !== null ? (string) $blobSASSignatureValues->ipRange : null;
        $signedProtocol = $blobSASSignatureValues->protocol?->value;
        $signedVersion = $blobSASSignatureValues->version ?? ApiVersion::LATEST->value;
        $signedSnapshotTime = $blobSASSignatureValues->snapshotTime ? (string) $blobSASSignatureValues->snapshotTime->getTimestamp() : null;

        $stringToSign = [
            $blobSASSignatureValues->permissions,
            $signedStart,
            $signedExpiry,
            self::computeCanonicalizedResource($blobSASSignatureValues, $sharedKeyCredential),
            $blobSASSignatureValues->identifier,
            $signedIp,
            $signedProtocol,
            $signedVersion,
            $signedResource,
            $signedSnapshotTime,
            $blobSASSignatureValues->encryptionScope,
            $blobSASSignatureValues->cacheControl,
            $blobSASSignatureValues->contentDisposition,
            $blobSASSignatureValues->contentEncoding,
            $blobSASSignatureValues->contentLanguage,
            $blobSASSignatureValues->contentType,
        ];
        $stringToSign = array_map(fn($str) => urldecode($str ?? ""), $stringToSign);
        $stringToSign = implode("\n", $stringToSign);

        $signature = urlencode($sharedKeyCredential->computeHMACSHA256($stringToSign));

        return new self(
            signedVersion: $signedVersion,
            signedResource: $signedResource,
            signedExpiry: $signedExpiry,
            signature: $signature,
            signedStart: $signedStart,
            signedPermissions: $blobSASSignatureValues->permissions,
            signedIdentifier: $blobSASSignatureValues->identifier,
            signedIp: $signedIp,
            cacheControl: $blobSASSignatureValues->cacheControl,
            contentDisposition: $blobSASSignatureValues->contentDisposition,
            contentEncoding: $blobSASSignatureValues->contentEncoding,
            contentLanguage: $blobSASSignatureValues->contentLanguage,
            contentType: $blobSASSignatureValues->contentType,
            signedProtocol: $signedProtocol,
            signedSnapshotTime: $signedSnapshotTime,
            signedEncryptionScope: $blobSASSignatureValues->encryptionScope,
        );
    }

    private static function computeCanonicalizedResource(
        BlobSASSignatureValues     $blobSASSignatureValues,
        StorageSharedKeyCredential $sharedKeyCredential,
    ): string {
        $resource = "/blob/$sharedKeyCredential->accountName/$blobSASSignatureValues->containerName";

        if ($blobSASSignatureValues->blobName !== null) {
            $resource .= "/$blobSASSignatureValues->blobName";
        }

        return $resource;
    }

    public function __toString(): string
    {
        return Query::build(array_filter([
            "st" => $this->signedStart,
            "sv" => $this->signedVersion,
            "sr" => $this->signedResource,
            "sig" => $this->signature,
            "sp" => $this->signedPermissions,
            "si" => $this->signedIdentifier,
            "se" => $this->signedExpiry,
            "sip" => $this->signedIp,
            "rscc" => $this->cacheControl,
            "rscd" => $this->contentDisposition,
            "rsce" => $this->contentEncoding,
            "rscl" => $this->contentLanguage,
            "rsct" => $this->contentType,
            "spr" => $this->signedProtocol,
            "sst" => $this->signedSnapshotTime,
            "ses" => $this->signedEncryptionScope,
        ]), false);
    }
}
