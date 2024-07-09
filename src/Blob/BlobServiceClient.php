<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob;

use AzureOss\Storage\Blob\Exceptions\InvalidConnectionStringException;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Common\Helpers\ConnectionStringHelper;
use Psr\Http\Message\UriInterface;

final class BlobServiceClient
{
    public function __construct(
        public UriInterface $uri,
        public readonly ?StorageSharedKeyCredential $sharedKeyCredentials = null,
    ) {}

    public static function fromConnectionString(string $connectionString): self
    {
        $uri = ConnectionStringHelper::getBlobEndpoint($connectionString);
        if ($uri === null) {
            throw new InvalidConnectionStringException();
        }

        $sas = ConnectionStringHelper::getSas($connectionString);
        if($sas !== null) {
            return new self($uri->withQuery($sas));
        }

        $accountName = ConnectionStringHelper::getAccountName($connectionString);
        $accountKey = ConnectionStringHelper::getAccountKey($connectionString);
        if ($accountName !== null && $accountKey !== null) {
            return new self($uri, new StorageSharedKeyCredential($accountName, $accountKey));
        }

        throw new InvalidConnectionStringException();
    }

    public function getContainerClient(string $containerName): BlobContainerClient
    {
        return new BlobContainerClient(
            $this->uri->withPath($this->uri->getPath() . "/" . $containerName),
            $this->sharedKeyCredentials,
        );
    }
}
