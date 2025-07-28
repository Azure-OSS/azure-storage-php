<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Common\Feature;

use AzureOss\Storage\Common\Auth\ClientSecretCredential;
use AzureOss\Storage\Common\Middleware\ClientFactory;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ClientSecretCredentialTest extends TestCase
{
    #[Test]
    public function get_token_works(): void
    {
        $tenantId = getenv('AZURE_STORAGE_BLOB_TENANT_ID');
        $clientId = getenv('AZURE_STORAGE_BLOB_CLIENT_ID');
        $clientSecret = getenv('AZURE_STORAGE_BLOB_CLIENT_SECRET');

        if ($tenantId === false || $clientId === false || $clientSecret === false) {
            self::markTestSkipped('Not all env variables have been set for this test');
        }

        $credential = new ClientSecretCredential($tenantId, $clientId, $clientSecret);

        $token = $credential->getToken();

        self::assertGreaterThan(0, strlen($token->accessToken));
        self::assertGreaterThan((new \DateTimeImmutable())->getTimestamp(), $token->expiresOn->getTimestamp());
    }

    #[Test]
    public function making_request_works(): void
    {
        $endpoint = getenv('AZURE_STORAGE_BLOB_ENDPOINT');
        $tenantId = getenv('AZURE_STORAGE_BLOB_TENANT_ID');
        $clientId = getenv('AZURE_STORAGE_BLOB_CLIENT_ID');
        $clientSecret = getenv('AZURE_STORAGE_BLOB_CLIENT_SECRET');

        if ($endpoint === false || $tenantId === false || $clientId === false || $clientSecret === false) {
            self::markTestSkipped('Not all env variables have been set for this test');
        }

        $client = (new ClientFactory())->create(
            credential: new ClientSecretCredential($tenantId, $clientId, $clientSecret),
        );

        $response = $client->get($endpoint . '/', [
            RequestOptions::QUERY => [
                'comp' => 'list',
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());
    }
}
