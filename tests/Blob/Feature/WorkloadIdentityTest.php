<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Common\Auth\WorkloadIdentityCredential;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WorkloadIdentityTest extends TestCase
{
    #[Test]
    public function from_workload_identity_creates_client_with_correct_credential(): void
    {
        // Mock environment variables
        $_ENV['AZURE_TENANT_ID'] = 'test-tenant-id';
        $_ENV['AZURE_CLIENT_ID'] = 'test-client-id';
        $_ENV['AZURE_FEDERATED_TOKEN_FILE'] = '/tmp/test-token';
        
        // Create a temporary token file
        file_put_contents('/tmp/test-token', 'mock-federated-token');

        try {
            $client = BlobServiceClient::fromWorkloadIdentity('teststorageaccount');

            self::assertEquals('https://teststorageaccount.blob.core.windows.net/', (string) $client->uri);
            self::assertInstanceOf(WorkloadIdentityCredential::class, $client->credentials);
            self::assertEquals('teststorageaccount', $client->credentials->storageAccountName);
            
            // Test legacy compatibility
            self::assertNull($client->getSharedKeyCredentials());
            self::assertFalse($client->canGenerateAccountSasUri());
        } finally {
            // Cleanup
            unlink('/tmp/test-token');
            unset($_ENV['AZURE_TENANT_ID'], $_ENV['AZURE_CLIENT_ID'], $_ENV['AZURE_FEDERATED_TOKEN_FILE']);
        }
    }

    #[Test]
    public function from_managed_identity_is_alias_for_workload_identity(): void
    {
        // Mock environment variables
        $_ENV['AZURE_TENANT_ID'] = 'test-tenant-id';
        $_ENV['AZURE_CLIENT_ID'] = 'test-client-id';
        $_ENV['AZURE_FEDERATED_TOKEN_FILE'] = '/tmp/test-token-2';
        
        // Create a temporary token file
        file_put_contents('/tmp/test-token-2', 'mock-federated-token');

        try {
            $client = BlobServiceClient::fromManagedIdentity('teststorageaccount');

            self::assertEquals('https://teststorageaccount.blob.core.windows.net/', (string) $client->uri);
            self::assertInstanceOf(WorkloadIdentityCredential::class, $client->credentials);
        } finally {
            // Cleanup
            unlink('/tmp/test-token-2');
            unset($_ENV['AZURE_TENANT_ID'], $_ENV['AZURE_CLIENT_ID'], $_ENV['AZURE_FEDERATED_TOKEN_FILE']);
        }
    }

    #[Test]
    public function workload_identity_credential_from_environment(): void
    {
        // Mock environment variables
        $_ENV['AZURE_TENANT_ID'] = 'test-tenant-id';
        $_ENV['AZURE_CLIENT_ID'] = 'test-client-id';
        $_ENV['AZURE_FEDERATED_TOKEN_FILE'] = '/tmp/test-token-3';

        try {
            $credential = WorkloadIdentityCredential::fromEnvironment('teststorageaccount');

            self::assertEquals('teststorageaccount', $credential->storageAccountName);
            self::assertEquals('test-tenant-id', $credential->tenantId);
            self::assertEquals('test-client-id', $credential->clientId);
            self::assertEquals('/tmp/test-token-3', $credential->federatedTokenFile);
        } finally {
            unset($_ENV['AZURE_TENANT_ID'], $_ENV['AZURE_CLIENT_ID'], $_ENV['AZURE_FEDERATED_TOKEN_FILE']);
        }
    }

    #[Test]
    public function container_client_inherits_workload_identity_credentials(): void
    {
        // Mock environment variables
        $_ENV['AZURE_TENANT_ID'] = 'test-tenant-id';
        $_ENV['AZURE_CLIENT_ID'] = 'test-client-id';
        $_ENV['AZURE_FEDERATED_TOKEN_FILE'] = '/tmp/test-token-4';
        
        // Create a temporary token file
        file_put_contents('/tmp/test-token-4', 'mock-federated-token');

        try {
            $serviceClient = BlobServiceClient::fromWorkloadIdentity('teststorageaccount');
            $containerClient = $serviceClient->getContainerClient('test-container');

            self::assertInstanceOf(WorkloadIdentityCredential::class, $containerClient->credentials);
            self::assertEquals('test-container', $containerClient->containerName);
            
            // Test legacy compatibility
            self::assertNull($containerClient->getSharedKeyCredentials());
            self::assertFalse($containerClient->canGenerateSasUri());
        } finally {
            // Cleanup
            unlink('/tmp/test-token-4');
            unset($_ENV['AZURE_TENANT_ID'], $_ENV['AZURE_CLIENT_ID'], $_ENV['AZURE_FEDERATED_TOKEN_FILE']);
        }
    }
}