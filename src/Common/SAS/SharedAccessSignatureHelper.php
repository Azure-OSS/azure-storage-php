<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\SAS;

use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;

class SharedAccessSignatureHelper
{
    public static function generateAccountSASQueryParameters(AccountSASSignatureValues $accountSASSignatureValues, StorageSharedKeyCredential $sharedKeyCredential): string
    {
        return "";
    }

    public static function generateBlobSASQueryParameters(BlobSASSignatureValues $blobSASSignatureValues, StorageSharedKeyCredential $sharedKeyCredential): string
    {
        return "";
    }
}
