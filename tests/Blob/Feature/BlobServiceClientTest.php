<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

final class BlobServiceClientTest extends BlobFeatureTestCase
{
    #[Test]
    public function from_connection_string_with_blob_endpoint_works(): void
    {
        $connectionString = "DefaultEndpointsProtocol=http;AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;BlobEndpoint=http://127.0.0.1:10000/devstoreaccount1;";
        $client = BlobServiceClient::fromConnectionString($connectionString);

        $this->assertNotNull($client->sharedKeyCredentials);
        $this->assertEquals('devstoreaccount1', $client->sharedKeyCredentials->accountName);
        $this->assertEquals('Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==', $client->sharedKeyCredentials->accountKey);
        $this->assertEquals("http://127.0.0.1:10000/devstoreaccount1", (string) $client->uri);
    }

    #[Test]
    public function from_connection_string_with_endpoint_suffix_works(): void
    {
        $connectionString = "DefaultEndpointsProtocol=https;AccountName=testing;AccountKey=Y2hlZXNlMWNoZWVzZTEyY2hlZXNlMTIzCg==;EndpointSuffix=core.windows.net";
        $client = BlobServiceClient::fromConnectionString($connectionString);

        $this->assertNotNull($client->sharedKeyCredentials);
        $this->assertEquals('testing', $client->sharedKeyCredentials->accountName);
        $this->assertEquals('Y2hlZXNlMWNoZWVzZTEyY2hlZXNlMTIzCg==', $client->sharedKeyCredentials->accountKey);
        $this->assertEquals("https://testing.blob.core.windows.net", (string) $client->uri);
    }

    #[Test]
    public function from_connection_string_with_developer_shortcut_works(): void
    {
        $connectionString = "UseDevelopmentStorage=true";
        $client = BlobServiceClient::fromConnectionString($connectionString);

        $this->assertNotNull($client->sharedKeyCredentials);
        $this->assertEquals('devstoreaccount1', $client->sharedKeyCredentials->accountName);
        $this->assertEquals('Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==', $client->sharedKeyCredentials->accountKey);
        $this->assertEquals("http://127.0.0.1:10000/devstoreaccount1", (string) $client->uri);
    }

    #[Test]
    public function create_container_client_works(): void
    {
        $connectionString = "UseDevelopmentStorage=true";

        $client = BlobServiceClient::fromConnectionString($connectionString);

        $containerClient = $client->getContainerClient("testing");

        $this->assertEquals($client->sharedKeyCredentials, $containerClient->sharedKeyCredentials);
        $this->assertEquals("http://127.0.0.1:10000/devstoreaccount1/testing", (string) $containerClient->uri);
    }
}
