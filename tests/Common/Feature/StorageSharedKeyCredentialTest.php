<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Common\Feature;

use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Common\Helpers\ConnectionStringHelper;
use AzureOss\Storage\Common\Middleware\ClientFactory;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StorageSharedKeyCredentialTest extends TestCase
{
    #[Test]
    public function making_request_works(): void
    {
        $connectionString = getenv('AZURE_STORAGE_BLOB_TEST_CONNECTION_STRING');

        if ($connectionString === false) {
            self::fail('Invalid connection string. Please set AZURE_STORAGE_BLOB_TEST_CONNECTION_STRING environment variable.');
        }

        $endpoint = ConnectionStringHelper::getBlobEndpoint($connectionString);
        $accountName = ConnectionStringHelper::getAccountName($connectionString);
        $accountKey = ConnectionStringHelper::getAccountKey($connectionString);


        if ($endpoint === null || $accountName === null || $accountKey === null) {
            self::fail('Invalid connection string. Please set AZURE_STORAGE_BLOB_TEST_CONNECTION_STRING environment variable.');
        }

        $client = (new ClientFactory())->create(
            credential: new StorageSharedKeyCredential($accountName, $accountKey),
        );

        $response = $client->get($endpoint . '/', [
            RequestOptions::QUERY => [
                'comp' => 'list',
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());
    }
}
