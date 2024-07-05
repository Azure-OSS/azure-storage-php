<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

use AzureOss\Storage\Blob\BlobContainerClient;
use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Blob\Exceptions\ContainerAlreadyExistsExceptionBlob;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundExceptionBlob;
use AzureOss\Storage\Blob\Models\Blob;
use AzureOss\Storage\Blob\Models\BlobPrefix;
use AzureOss\Storage\Blob\Sas\BlobSasBuilder;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\Test;

final class BlobContainerClientTest extends BlobFeatureTestCase
{
    private BlobContainerClient $containerClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->containerClient = $this->serviceClient->getContainerClient("blobclienttests");
        $this->cleanContainer($this->containerClient->containerName);
    }

    #[Test]
    public function create_blob_client_works(): void
    {
        $connectionString = "UseDevelopmentStorage=true";

        $client = BlobServiceClient::fromConnectionString($connectionString);

        $containerClient = $client->getContainerClient("testing");
        $blobClient = $containerClient->getBlobClient("some/file.txt");

        $this->assertEquals($blobClient->sharedKeyCredentials, $containerClient->sharedKeyCredentials);
        $this->assertEquals("http://127.0.0.1:10000/devstoreaccount1/testing/some/file.txt", (string) $blobClient->uri);
    }

    #[Test]
    public function create_works(): void
    {
        $containerClient = $this->serviceClient->getContainerClient($this->randomContainerName());
        $containerClient->deleteIfExists();

        $this->assertFalse($containerClient->exists());

        $containerClient->create();

        $this->assertTrue($containerClient->exists());

        $containerClient->delete(); // cleanup
    }

    #[Test]
    public function create_throws_when_container_already_exists(): void
    {
        $this->expectException(ContainerAlreadyExistsExceptionBlob::class);

        $this->containerClient->create();
    }

    #[Test]
    public function create_if_not_exists_works(): void
    {
        $containerClient = $this->serviceClient->getContainerClient($this->randomContainerName());
        $containerClient->deleteIfExists();

        $this->assertFalse($containerClient->exists());

        $containerClient->createIfNotExists();

        $this->assertTrue($containerClient->exists());
    }

    #[Test]
    public function create_if_not_exists_doesnt_throw_when_container_already_exists(): void
    {
        $this->expectNotToPerformAssertions();

        $this->containerClient->createIfNotExists();
    }

    #[Test]
    public function delete_works(): void
    {
        $containerClient = $this->serviceClient->getContainerClient($this->randomContainerName());
        $containerClient->create();

        $this->assertTrue($containerClient->exists());

        $containerClient->delete();

        $this->assertFalse($containerClient->exists());
    }

    #[Test]
    public function delete_throws_when_container_doesnt_exists(): void
    {
        $this->expectException(ContainerNotFoundExceptionBlob::class);

        $this->serviceClient->getContainerClient("noop")->delete();
    }

    #[Test]
    public function delete_if_exists_works(): void
    {
        $containerClient = $this->serviceClient->getContainerClient($this->randomContainerName());
        $containerClient->create();

        $this->assertTrue($containerClient->exists());

        $containerClient->deleteIfExists();

        $this->assertFalse($containerClient->exists());
    }

    #[Test]
    public function delete_if_exists_doesnt_throw_when_container_doesnt_exists(): void
    {
        $this->expectNotToPerformAssertions();

        $this->serviceClient->getContainerClient("noop")->deleteIfExists();
    }

    #[Test]
    public function exists_works(): void
    {
        $containerClient = $this->serviceClient->getContainerClient($this->randomContainerName());
        $containerClient->create();

        $this->assertTrue($containerClient->exists());

        $containerClient->delete();

        $this->assertFalse($containerClient->exists());
    }

    #[Test]
    public function get_blobs_works(): void
    {
        $this->containerClient->getBlobClient("fileA.txt")->upload("test");
        $this->containerClient->getBlobClient("fileB.txt")->upload("test");
        $this->containerClient->getBlobClient("some/fileB.txt")->upload("test");
        $this->containerClient->getBlobClient("some/deeply/nested/fileB.txt")->upload("test");

        $blobs = iterator_to_array($this->containerClient->getBlobs());

        $this->assertCount(4, $blobs);
    }

    #[Test]
    public function get_blobs_works_with_prefix(): void
    {
        $this->containerClient->getBlobClient("fileA.txt")->upload("test");
        $this->containerClient->getBlobClient("fileB.txt")->upload("test");
        $this->containerClient->getBlobClient("some/fileB.txt")->upload("test");
        $this->containerClient->getBlobClient("some/deeply/nested/fileB.txt")->upload("test");

        $blobs = iterator_to_array($this->containerClient->getBlobs("some/"));

        $this->assertCount(2, $blobs);
    }

    #[Test]
    public function get_blobs_throws_if_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundExceptionBlob::class);

        iterator_to_array($this->serviceClient->getContainerClient("noop")->getBlobs());
    }

    #[Test]
    public function get_blobs_by_hierarchy_works(): void
    {
        $this->containerClient->getBlobClient("fileA.txt")->upload("test");
        $this->containerClient->getBlobClient("fileB.txt")->upload("test");
        $this->containerClient->getBlobClient("some/fileB.txt")->upload("test");
        $this->containerClient->getBlobClient("some/deeply/nested/fileB.txt")->upload("test");

        $results = iterator_to_array($this->containerClient->getBlobsByHierarchy());

        $blobs = array_filter($results, fn($item) => $item instanceof Blob);
        $prefixes = array_filter($results, fn($item) => $item instanceof BlobPrefix);

        $this->assertCount(2, $blobs);
        $this->assertCount(1, $prefixes);
    }

    #[Test]
    public function get_blobs_by_hierarchy_works_with_prefix(): void
    {
        $this->containerClient->getBlobClient("fileA.txt")->upload("test");
        $this->containerClient->getBlobClient("fileB.txt")->upload("test");
        $this->containerClient->getBlobClient("some/fileB.txt")->upload("test");
        $this->containerClient->getBlobClient("some/deeply/nested/fileB.txt")->upload("test");

        $results = iterator_to_array($this->containerClient->getBlobsByHierarchy("some/"));

        $blobs = array_filter($results, fn($item) => $item instanceof Blob);
        $prefixes = array_filter($results, fn($item) => $item instanceof BlobPrefix);

        $this->assertCount(1, $blobs);
        $this->assertCount(1, $prefixes);
    }

    #[Test]
    public function get_blobs_by_hierarchy_works_with_different_delimiter(): void
    {
        $this->containerClient->getBlobClient("fileA.txt")->upload("test");
        $this->containerClient->getBlobClient("fileB.txt")->upload("test");
        $this->containerClient->getBlobClient("some-fileB.txt")->upload("test");
        $this->containerClient->getBlobClient("some-deeply-nested-fileB.txt")->upload("test");

        $results = iterator_to_array($this->containerClient->getBlobsByHierarchy(delimiter: "-"));

        $blobs = array_filter($results, fn($item) => $item instanceof Blob);
        $prefixes = array_filter($results, fn($item) => $item instanceof BlobPrefix);

        $this->assertCount(2, $blobs);
        $this->assertCount(1, $prefixes);
    }

    #[Test]
    public function get_blobs_by_hierarchy_throws_if_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundExceptionBlob::class);

        iterator_to_array($this->serviceClient->getContainerClient("noop")->getBlobsByHierarchy());
    }

    #[Test]
    public function can_generate_sas_uri_works(): void
    {
        $containerClient = new BlobContainerClient(new Uri("https://testing.blob.core.windows.net/testing"));

        $this->assertFalse($containerClient->canGenerateSasUri());

        $containerClient = new BlobContainerClient(
            new Uri("https://testing.blob.core.windows.net/testing"),
            new StorageSharedKeyCredential("noop", "noop"),
        );

        $this->assertTrue($containerClient->canGenerateSasUri());
    }

    #[Test]
    public function generate_sas_uri_works(): void
    {
        $this->expectNotToPerformAssertions();

        $sas = $this->containerClient->generateSasUri(
            BlobSasBuilder::new()
                ->setPermissions("l")
                ->setExpiresOn((new \DateTime())->modify("+ 1min")),
        );

        $sasServiceClient = new BlobContainerClient($sas);

        iterator_to_array($sasServiceClient->getBlobs());
    }
}
