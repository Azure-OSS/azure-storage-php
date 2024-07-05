<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

use AzureOss\Storage\Blob\BlobClient;
use AzureOss\Storage\Blob\BlobContainerClient;
use AzureOss\Storage\Blob\Exceptions\BlobNotFoundExceptionBlob;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundExceptionBlob;
use AzureOss\Storage\Blob\Models\UploadBlobOptions;
use AzureOss\Storage\Blob\Sas\BlobSasBuilder;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\StreamInterface;

final class BlobClientTest extends BlobFeatureTestCase
{
    private BlobContainerClient $containerClient;
    private BlobClient $blobClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->containerClient = $this->serviceClient->getContainerClient("blobclienttests");
        $this->blobClient = $this->containerClient->getBlobClient("some/file.txt");

        $this->containerClient->deleteIfExists(); // cleanup
    }

    #[Test]
    public function download_stream_works(): void
    {
        $this->containerClient->create();

        $content = "Lorem ipsum dolor sit amet";
        $this->blobClient->upload($content, new UploadBlobOptions("text/plain"));

        $result = $this->blobClient->downloadStreaming();

        $this->assertEquals($result->properties->contentLength, strlen($content));
        $this->assertEquals("text/plain", $result->properties->contentType);
        $this->assertEquals($content, $result->content->getContents());
    }

    #[Test]
    public function download_streams_throws_if_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundExceptionBlob::class);

        $this->blobClient->downloadStreaming();
    }

    #[Test]
    public function download_stream_throws_if_blob_doesnt_exist(): void
    {
        $this->containerClient->create();

        $this->expectException(BlobNotFoundExceptionBlob::class);

        $this->blobClient->downloadStreaming();
    }

    #[Test]
    public function get_properties_works(): void
    {
        $this->containerClient->create();

        $content = "Lorem ipsum dolor sit amet";
        $this->blobClient->upload($content, new UploadBlobOptions("text/plain"));

        $result = $this->blobClient->getProperties();

        $this->assertEquals($result->contentLength, strlen($content));
        $this->assertEquals("text/plain", $result->contentType);
    }

    #[Test]
    public function get_properties_throws_if_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundExceptionBlob::class);

        $this->blobClient->getProperties();
    }

    #[Test]
    public function get_properties_throws_if_blob_doesnt_exist(): void
    {
        $this->containerClient->create();

        $this->expectException(BlobNotFoundExceptionBlob::class);

        $this->blobClient->getProperties();
    }

    #[Test]
    public function delete_works(): void
    {
        $this->containerClient->create();

        $this->blobClient->upload("");

        $this->assertTrue($this->blobClient->exists());

        $this->blobClient->delete();

        $this->assertFalse($this->blobClient->exists());
    }

    #[Test]
    public function delete_works_throws_if_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundExceptionBlob::class);

        $this->blobClient->delete();
    }

    #[Test]
    public function delete_works_throws_if_blob_doesnt_exist(): void
    {
        $this->containerClient->create();

        $this->expectException(BlobNotFoundExceptionBlob::class);

        $this->blobClient->delete();
    }

    #[Test]
    public function delete_if_exists_works(): void
    {
        $this->containerClient->create();

        $this->blobClient->upload("");

        $this->assertTrue($this->blobClient->exists());

        $this->blobClient->deleteIfExists();

        $this->assertFalse($this->blobClient->exists());
    }

    public function delete_if_exists_throws_if_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundExceptionBlob::class);

        $this->blobClient->deleteIfExists();
    }

    #[Test]
    public function delete_if_exists_doesnt_throws_if_blob_doesnt_exist(): void
    {
        $this->containerClient->create();

        $this->expectNotToPerformAssertions();

        $this->blobClient->deleteIfExists();
    }

    #[Test]
    public function exists_works(): void
    {
        $this->containerClient->create();

        $this->assertFalse($this->blobClient->exists());

        $this->blobClient->upload("");

        $this->assertTrue($this->blobClient->exists());
    }

    #[Test]
    public function exists_works_throws_if_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundExceptionBlob::class);

        $this->blobClient->exists();
    }

    #[Test]
    public function upload_works_with_single_upload(): void
    {
        $this->containerClient->create();

        $this->withFile(1000, function (StreamInterface $file) {
            $beforeUploadContent = $file->getContents();
            $file->rewind();

            $this->blobClient->upload($file, new UploadBlobOptions("text/plain", initialTransferSize: 2000));

            $properties = $this->blobClient->getProperties();

            $this->assertEquals("text/plain", $properties->contentType);
            $this->assertEquals(1000, $properties->contentLength);

            $afterUploadContent = $this->blobClient->downloadStreaming()->content;

            $this->assertEquals($beforeUploadContent, $afterUploadContent);
        });
    }

    #[Test]
    public function upload_works_with_parallel_upload(): void
    {
        $this->containerClient->create();

        $this->withFile(1000, function (StreamInterface $file) {
            $beforeUploadContent = $file->getContents();
            $file->rewind();

            $this->blobClient->upload($file, new UploadBlobOptions("text/plain", initialTransferSize: 500, maximumTransferSize: 100));

            $properties = $this->blobClient->getProperties();

            $this->assertEquals("text/plain", $properties->contentType);
            $this->assertEquals(1000, $properties->contentLength);

            $afterUploadContent = $this->blobClient->downloadStreaming()->content;

            $this->assertEquals($beforeUploadContent, $afterUploadContent);
        });
    }

    #[Test]
    public function upload_throws_if_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundExceptionBlob::class);

        $this->blobClient->upload("");
    }

    #[Test]
    public function copy_from_url_works(): void
    {
        $sourceContainerClient = $this->serviceClient->getContainerClient("blobclienttestscopy");
        $sourceContainerClient->deleteIfExists(); // cleanup

        $sourceContainerClient->create();
        $this->containerClient->create();

        $sourceBlobClient = $sourceContainerClient->getBlobClient("to_copy");
        $sourceBlobClient->upload("This should be copied!");

        $this->blobClient->copyFromUri($sourceBlobClient->uri);

        $sourceContent = $sourceBlobClient->downloadStreaming()->content->getContents();
        $targetContent = $this->blobClient->downloadStreaming()->content->getContents();

        $this->assertEquals($targetContent, $sourceContent);
    }

    #[Test]
    public function copy_from_url_works_throws_if_source_container_doesnt_exist(): void
    {
        $sourceContainerClient = $this->serviceClient->getContainerClient("blobclienttestscopy");
        $sourceContainerClient->deleteIfExists(); // cleanup

        $this->containerClient->create();

        $sourceBlobClient = $sourceContainerClient->getBlobClient("to_copy");
        $this->expectException(ContainerNotFoundExceptionBlob::class);

        $this->blobClient->copyFromUri($sourceBlobClient->uri);
    }

    #[Test]
    public function copy_from_url_works_throws_if_source_blob_doesnt_exist(): void
    {
        $sourceContainerClient = $this->serviceClient->getContainerClient("blobclienttestscopy");
        $sourceContainerClient->deleteIfExists(); // cleanup

        $this->containerClient->create();
        $sourceContainerClient->create();

        $sourceBlobClient = $sourceContainerClient->getBlobClient("to_copy");
        $this->expectException(BlobNotFoundExceptionBlob::class);

        $this->blobClient->copyFromUri($sourceBlobClient->uri);
    }

    #[Test]
    public function generate_sas_uri_works(): void
    {
        $this->expectNotToPerformAssertions();

        $this->containerClient->create();

        $blobClient = $this->containerClient->getBlobClient("blob");
        $blobClient->upload("");

        $sas = $blobClient->generateSasUri(
            BlobSasBuilder::new()
                ->setPermissions("r")
                ->setExpiresOn((new \DateTime())->modify("+ 1min")),
        );

        $sasBlobClient = new BlobClient($sas);

        $sasBlobClient->downloadStreaming();
    }
}
