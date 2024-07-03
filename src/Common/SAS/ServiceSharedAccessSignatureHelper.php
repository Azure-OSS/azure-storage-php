<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\SAS;

use AzureOss\Storage\Common\ApiVersion;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use GuzzleHttp\Psr7\Query;

class ServiceSharedAccessSignatureHelper
{
    /**
     * @param SASPermission[] $permissions
     */
    public static function generateQuery(
        StorageSharedKeyCredential $sharedKeyCredential,
        string $resourceName,
        SASResource $resource,
        \DateTimeInterface $expiresOn,
        array $permissions,
        ApiVersion $version = ApiVersion::LATEST,
        SASProtocol $protocol = SASProtocol::HTTPS_AND_HTTP,
        ?\DateTimeInterface $startsOn = null,
        ?SASIpRange $ipRange = null,
        ?string $identifier = null,
        ?\DateTimeInterface $snapshotTime = null,
        ?string $encryptionScope = null,
        ?string $cacheControl = null,
        ?string $contentDisposition = null,
        ?string $contentEncoding = null,
        ?string $contentLanguage = null,
        ?string $contentType = null,
    ): string {
        $sp = self::computeCanonicalizedPermissions($permissions, $resource);
        $st = $startsOn ? self::formatDate($startsOn) : null;
        $se = self::formatDate($expiresOn);
        $canonicalizedResource = self::computeCanonicalizedResource($sharedKeyCredential->accountName, $signedResource, $resourceName);
        $si = $identifier;
        $sip = $ipRange !== null ? (string) $ipRange : null;
        $spr = $protocol->value;
        $sv = $version->value;
        $sr = $signedResource->value;
        $ses = $encryptionScope;
        $rscc = $cacheControl;
        $rscd = $contentDisposition;
        $rsce = $contentEncoding;
        $rscl = $contentLanguage;
        $rsct = $contentType;
        $sst = $snapshotTime->getTimestamp();

        $stringToSign = implode("\n", array_map(urldecode(...), [
            $sp,
            $st,
            $se,
            $canonicalizedResource,
            $si,
            $sip,
            $spr,
            $sv,
            $sr,
            $sst,
            $ses,
            $rscc,
            $rscd,
            $rsce,
            $rscl,
            $rsct
        ]));

        $sig = urlencode($sharedKeyCredential->computeHMACSHA256($stringToSign));

        return Query::build(array_filter([
            "sp" => $sp,
            "st" => $st,
            "se" => $se,
            "spr" => $spr,
            "sv" => $sv,
            "sr" => $sr,
            "sst" => $sst,
            "ses" => $ses,
            "rscc" => $rscc,
            "rscd" => $rscd,
            "rsce" => $rsce,
            "rscl" => $rscl,
            "rsct" => $rsct,
            "sip" => $sip,
            "si" => $si,
            'sig' => $sig
        ]), false);
    }

    /**
     * @param SASPermission[] $signedPermissions
     */
    private static function computeCanonicalizedPermissions(array $signedPermissions, SASResource $resource): string
    {
        $canonicalizedPermissions = "";
        foreach ($resource->getOrderOfPermissions() as $nextPermission) {
            foreach ($signedPermissions as $permission) {
                if ($permission === $nextPermission) {
                    $canonicalizedPermissions .= $permission->value;
                }
            }
        }

        return $canonicalizedPermissions;
    }

    private static function computeCanonicalizedResource(string $accountName, SASResource $resource, string $resourceName): string
    {
        return urldecode(sprintf('/%s/%s/%s', $resource->getResourceType()->value, $accountName, $resourceName));
    }

    private static function formatDate(\DateTimeInterface $date): string
    {
        $date = clone $date;
        $date = $date->setTimezone(new \DateTimeZone('UTC'));

        return str_replace('+00:00', 'Z', $date->format('c'));
    }
}
