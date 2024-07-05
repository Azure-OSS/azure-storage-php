<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Clients;

use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;

final class BlobServiceClient
{
    public function __construct(
        public readonly string      $blobEndpoint,
        public readonly StorageSharedKeyCredential $sharedKeyCredentials,
    ) {}

    public static function fromConnectionString(string $connectionString): self
    {
        if ($connectionString === 'UseDevelopmentStorage=true') {
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
            $blobEndpoint,
            new StorageSharedKeyCredential($settings['AccountName'], $settings['AccountKey']),
        );
    }

    private static function fromDevConnectionString(): self
    {
        return new self(
            'http://127.0.0.1:10000/devstoreaccount1',
            new StorageSharedKeyCredential(
                'devstoreaccount1',
                'Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==',
            ),
        );
    }

    public function getContainerClient(string $containerName): BlobContainerClient
    {
        return new BlobContainerClient(
            $this->blobEndpoint,
            $containerName,
            $this->sharedKeyCredentials,
        );
    }
}
