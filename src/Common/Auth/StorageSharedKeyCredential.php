<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Auth;

use AzureOss\Storage\Common\Exceptions\InvalidAccountKeyException;

/**
 * @see https://learn.microsoft.com/en-us/rest/api/storageservices/authorize-with-shared-key
 */
final class StorageSharedKeyCredential
{
    public function __construct(
        public readonly string $accountName,
        public readonly string $accountKey,
    ) {}

    /**
     * @throws InvalidAccountKeyException
     */
    public function computeHMACSHA256(string $stringToSign): string
    {
        $decodedAccountKey = base64_decode($this->accountKey, true);

        if ($decodedAccountKey === false) {
            throw new InvalidAccountKeyException();
        }

        return base64_encode(
            hash_hmac('sha256', $stringToSign, $decodedAccountKey, true),
        );
    }
}
