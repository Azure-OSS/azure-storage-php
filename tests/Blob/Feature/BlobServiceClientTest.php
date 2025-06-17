<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Blob\Exceptions\InvalidConnectionStringException;
use AzureOss\Storage\Blob\Exceptions\UnableToGenerateSasException;
use AzureOss\Storage\Common\ApiVersion;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Common\Sas\AccountSasBuilder;
use AzureOss\Storage\Common\Sas\AccountSasPermissions;
use AzureOss\Storage\Common\Sas\AccountSasResourceTypes;
use AzureOss\Storage\Common\Sas\SasIpRange;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\Test;

final class BlobServiceClientTest extends BlobFeatureTestCase
{
    #[Test]
    public function from_connection_string_with_blob_endpoint_works(): void
    {
        $connectionString = "DefaultEndpointsProtocol=http;AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;BlobEndpoint=http://127.0.0.1:10000/devstoreaccount1;";
        $client = BlobServiceClient::fromConnectionString($connectionString);

        self::assertInstanceOf(StorageSharedKeyCredential::class, $client->credential);
        self::assertEquals('devstoreaccount1', $client->credential->accountName);
        self::assertEquals('Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==', $client->credential->accountKey);
        self::assertEquals("http://127.0.0.1:10000/devstoreaccount1/", (string) $client->uri);
    }

    #[Test]
    public function from_connection_string_with_endpoint_suffix_works(): void
    {
        $connectionString = "DefaultEndpointsProtocol=https;AccountName=testing;AccountKey=Y2hlZXNlMWNoZWVzZTEyY2hlZXNlMTIzCg==;EndpointSuffix=core.windows.net";
        $client = BlobServiceClient::fromConnectionString($connectionString);

        self::assertInstanceOf(StorageSharedKeyCredential::class, $client->credential);
        self::assertEquals('testing', $client->credential->accountName);
        self::assertEquals('Y2hlZXNlMWNoZWVzZTEyY2hlZXNlMTIzCg==', $client->credential->accountKey);
        self::assertEquals("https://testing.blob.core.windows.net/", (string) $client->uri);
    }

    #[Test]
    public function from_connection_string_with_developer_shortcut_works(): void
    {
        $connectionString = "UseDevelopmentStorage=true";
        $client = BlobServiceClient::fromConnectionString($connectionString);

        self::assertInstanceOf(StorageSharedKeyCredential::class, $client->credential);
        self::assertEquals('devstoreaccount1', $client->credential->accountName);
        self::assertEquals('Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==', $client->credential->accountKey);
        self::assertEquals("http://127.0.0.1:10000/devstoreaccount1/", (string) $client->uri);
    }

    #[Test]
    public function from_connection_string_without_account_name_throws(): void
    {
        $this->expectException(InvalidConnectionStringException::class);
        $connectionString = "DefaultEndpointsProtocol=https;AccountKey=Y2hlZXNlMWNoZWVzZTEyY2hlZXNlMTIzCg==;EndpointSuffix=core.windows.net";
        BlobServiceClient::fromConnectionString($connectionString);
    }

    #[Test]
    public function from_connection_string_without_account_key_throws(): void
    {
        $this->expectException(InvalidConnectionStringException::class);
        $connectionString = "DefaultEndpointsProtocol=https;AccountName=testing;EndpointSuffix=core.windows.net";
        BlobServiceClient::fromConnectionString($connectionString);
    }

    #[Test]
    public function from_connection_string_without_blob_endpoint_and_without_endpoint_suffix_throws(): void
    {
        $this->expectException(InvalidConnectionStringException::class);
        $connectionString = "DefaultEndpointsProtocol=https;AccountName=testing;AccountKey=Y2hlZXNlMWNoZWVzZTEyY2hlZXNlMTIzCg==";
        BlobServiceClient::fromConnectionString($connectionString);
    }

    #[Test]
    public function from_connection_string_with_sas_works(): void
    {
        $connectionString = "BlobEndpoint=https://storagesample.blob.core.windows.net;SharedAccessSignature=sv=2015-07-08&sig=iCvQmdZngZNW%2F4vw43j6%2BVz6fndHF5LI639QJba4r8o%3D&spr=https&st=2016-04-12T03%3A24%3A31Z&se=2016-04-13T03%3A29%3A31Z&srt=s&ss=bf&sp=rwl";
        $client = BlobServiceClient::fromConnectionString($connectionString);

        self::assertNull($client->credential);
        self::assertEquals("https://storagesample.blob.core.windows.net/?sv=2015-07-08&sig=iCvQmdZngZNW%2F4vw43j6%2BVz6fndHF5LI639QJba4r8o%3D&spr=https&st=2016-04-12T03%3A24%3A31Z&se=2016-04-13T03%3A29%3A31Z&srt=s&ss=bf&sp=rwl", (string) $client->uri);
    }

    #[Test]
    public function from_connection_string_without_account_key_and_without_sas_throws(): void
    {
        $this->expectException(InvalidConnectionStringException::class);
        $connectionString = "BlobEndpoint=http://127.0.0.1:10000/devstoreaccount1;";
        BlobServiceClient::fromConnectionString($connectionString);
    }

    #[Test]
    public function from_connection_string_default_endpoint_protocol_overwrites_protocol_of_blob_endpoint(): void
    {
        $connectionString = "DefaultEndpointsProtocol=https;AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;BlobEndpoint=http://127.0.0.1:10000/devstoreaccount1;";
        $client = BlobServiceClient::fromConnectionString($connectionString);

        self::assertEquals("https://127.0.0.1:10000/devstoreaccount1/", (string) $client->uri);
    }

    #[Test]
    public function create_container_client_works(): void
    {
        $connectionString = "UseDevelopmentStorage=true";

        $client = BlobServiceClient::fromConnectionString($connectionString);

        $containerClient = $client->getContainerClient("testing");

        self::assertEquals($client->credential, $containerClient->credential);
        self::assertEquals("http://127.0.0.1:10000/devstoreaccount1/testing", (string) $containerClient->uri);
    }

    #[Test]
    public function get_containers_works(): void
    {
        $before = iterator_to_array($this->serviceClient->getBlobContainers());

        $this->serviceClient->getContainerClient($this->randomContainerName())->create();

        $after = iterator_to_array($this->serviceClient->getBlobContainers());

        self::assertCount(count($before) + 1, $after);
    }

    #[Test]
    public function get_containers_works_with_prefix(): void
    {
        $name = $this->randomContainerName();
        $this->serviceClient->getContainerClient($name)->create();

        $after = iterator_to_array($this->serviceClient->getBlobContainers($name));

        self::assertCount(1, $after);
    }

    #[Test]
    public function find_blobs_by_tag_works(): void
    {
        $containerClient = $this->serviceClient->getContainerClient("tagging");
        $containerClient->createIfNotExists();

        $blobClient = $containerClient->getBlobClient('tagged');
        $blobClient->deleteIfExists();
        $blobClient->upload("");
        $blobClient->setTags(['foo' => 'blobservice']);

        sleep(1); // tagging doesn't seem to be instant

        self::assertCount(0, iterator_to_array($this->serviceClient->findBlobsByTag("foo = 'noop'")));
        self::assertCount(1, iterator_to_array($this->serviceClient->findBlobsByTag("foo = 'blobservice'")));
    }

    #[Test]
    public function can_generate_account_sas_uri_works(): void
    {
        $containerClient = new BlobServiceClient(new Uri("https://testing.blob.core.windows.net"));

        self::assertFalse($containerClient->canGenerateAccountSasUri());

        $containerClient = new BlobServiceClient(
            new Uri("https://testing.blob.core.windows.net"),
            new StorageSharedKeyCredential("noop", "noop"),
        );

        self::assertTrue($containerClient->canGenerateAccountSasUri());
    }

    #[Test]
    public function generate_account_sas_uri_works(): void
    {
        $this->expectNotToPerformAssertions();

        $sas = $this->serviceClient->generateAccountSasUri(
            AccountSasBuilder::new()
                ->setPermissions(new AccountSasPermissions(list: true))
                ->setResourceTypes(new AccountSasResourceTypes(service: true))
                ->setIpRange(new SasIpRange("0.0.0.0", "255.255.255.255"))
                ->setVersion(ApiVersion::LATEST->value)
                ->setStartsOn((new \DateTime()))
                ->setExpiresOn((new \DateTime())->modify("+ 1min")),
        );

        $sasServiceClient = new BlobServiceClient($sas);

        iterator_to_array($sasServiceClient->getBlobContainers());
    }

    #[Test]
    public function generate_account_sas_throws_when_there_are_no_shared_key_credential(): void
    {
        $this->expectException(UnableToGenerateSasException::class);

        $serviceClientWithoutCredential = new BlobServiceClient(new Uri("example.com"));

        $serviceClientWithoutCredential->generateAccountSasUri(
            AccountSasBuilder::new(),
        );
    }
}
