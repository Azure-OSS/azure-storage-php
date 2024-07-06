<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob;

use AzureOss\Storage\Blob\Exceptions\InvalidConnectionStringException;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Common\ConnectionStringParser;
use Psr\Http\Message\UriInterface;

final class BlobServiceClient
{
    public function __construct(
        public UriInterface $uri,
        public readonly ?StorageSharedKeyCredential $sharedKeyCredentials = null,
    ) {}

    public static function fromConnectionString(string $connectionString): self
    {
        $uri = ConnectionStringParser::getBlobEndpoint($connectionString);
        if ($uri === null) {
            throw new InvalidConnectionStringException();
        }

        $sas = ConnectionStringParser::getSas($connectionString);
        if($sas !== null) {
            return new self($uri->withQuery($sas));
        }

        $accountName = ConnectionStringParser::getAccountName($connectionString);
        $accountKey = ConnectionStringParser::getAccountKey($connectionString);
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
