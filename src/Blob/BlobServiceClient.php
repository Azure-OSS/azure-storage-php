<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob;

use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

final class BlobServiceClient
{
    private const DEV_CONNECTION_STRING_SHORTCUT = 'UseDevelopmentStorage=true';

    private const DEV_BLOB_ENDPOINT = 'http://127.0.0.1:10000/devstoreaccount1';

    private const DEV_BLOB_ACCOUNT_NAME = "devstoreaccount1";

    private const DEV_BLOB_ACCOUNT_KEY = "Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==";

    public function __construct(
        public UriInterface $uri,
        public readonly ?StorageSharedKeyCredential $sharedKeyCredentials = null,
    ) {}

    public static function fromConnectionString(string $connectionString): self
    {
        if ($connectionString === self::DEV_CONNECTION_STRING_SHORTCUT) {
            return self::fromDevConnectionString();
        }

        $settings = [];
        foreach (explode(';', $connectionString) as $segment) {
            if (!empty($segment)) {
                [$key, $value] = explode('=', $segment, 2);
                $settings[$key] = $value;
            }
        }

        $blobEndpoint = $settings['BlobEndpoint']
            ?? sprintf(
                '%s://%s.blob.%s',
                $settings['DefaultEndpointsProtocol'],
                $settings['AccountName'],
                $settings['EndpointSuffix'],
            );

        return new self(
            new Uri($blobEndpoint),
            new StorageSharedKeyCredential($settings['AccountName'], $settings['AccountKey']),
        );
    }

    private static function fromDevConnectionString(): self
    {
        return new self(
            new Uri(self::DEV_BLOB_ENDPOINT),
            new StorageSharedKeyCredential(
                self::DEV_BLOB_ACCOUNT_NAME,
                self::DEV_BLOB_ACCOUNT_KEY,
            ),
        );
    }

    public function getContainerClient(string $containerName): BlobContainerClient
    {
        return new BlobContainerClient(
            $this->uri->withPath($this->uri->getPath() . "/" . $containerName),
            $this->sharedKeyCredentials,
        );
    }
}
