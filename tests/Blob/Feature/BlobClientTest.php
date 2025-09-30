<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

use AzureOss\Storage\Blob\BlobClient;
use AzureOss\Storage\Blob\BlobContainerClient;
use AzureOss\Storage\Blob\Exceptions\BlobNotFoundException;
use AzureOss\Storage\Blob\Exceptions\CannotVerifyCopySourceException;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Blob\Exceptions\NoPendingCopyOperationException;
use AzureOss\Storage\Blob\Exceptions\TagsTooLargeException;
use AzureOss\Storage\Blob\Models\BlobHttpHeaders;
use AzureOss\Storage\Blob\Models\CopyStatus;
use AzureOss\Storage\Blob\Models\UploadBlobOptions;
use AzureOss\Storage\Blob\Sas\BlobSasBuilder;
use AzureOss\Storage\Blob\Sas\BlobSasPermissions;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use AzureOss\Storage\Tests\Utils\FileFactory;
use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\StreamInterface;

final class BlobClientTest extends BlobFeatureTestCase
{
    private BlobContainerClient $containerClient;
    private BlobClient $blobClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->containerClient = $this->serviceClient->getContainerClient("blobclient");
        $this->blobClient = $this->containerClient->getBlobClient("some/file.txt");
        $this->cleanContainer($this->containerClient->containerName);
    }

    #[Test]
    public function download_stream_works(): void
    {
        $content = "Lorem ipsum dolor sit amet";
        $this->blobClient->upload($content, new UploadBlobOptions("text/plain"));

        $result = $this->blobClient->downloadStreaming();

        self::assertEquals($result->properties->contentLength, strlen($content));
        self::assertEquals("text/plain", $result->properties->contentType);
        self::assertEquals($content, $result->content->getContents());
    }

    #[Test]
    public function download_streams_throws_if_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient('noop')->getBlobClient('noop')->downloadStreaming();
    }

    #[Test]
    public function download_stream_throws_if_blob_doesnt_exist(): void
    {
        $this->expectException(BlobNotFoundException::class);

        $this->blobClient->downloadStreaming();
    }

    #[Test]
    public function get_properties_works(): void
    {
        $content = "Lorem ipsum dolor sit amet";
        $this->blobClient->upload($content, new UploadBlobOptions("text/plain"));

        $result = $this->blobClient->getProperties();

        self::assertEquals($result->contentLength, strlen($content));
        self::assertEquals("text/plain", $result->contentType);
    }

    #[Test]
    public function get_properties_throws_if_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient('noop')->getBlobClient('noop')->getProperties();
    }

    #[Test]
    public function get_properties_throws_if_blob_doesnt_exist(): void
    {
        $this->expectException(BlobNotFoundException::class);

        $this->blobClient->getProperties();
    }

    #[Test]
    public function delete_works(): void
    {
        $this->blobClient->upload("test");

        self::assertTrue($this->blobClient->exists());

        $this->blobClient->delete();

        self::assertFalse($this->blobClient->exists());
    }

    #[Test]
    public function delete_works_throws_if_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient('noop')->getBlobClient('noop')->delete();
    }

    #[Test]
    public function delete_works_throws_if_blob_doesnt_exist(): void
    {
        $this->expectException(BlobNotFoundException::class);

        $this->blobClient->deleteIfExists();
        $this->blobClient->delete();
    }

    #[Test]
    public function delete_if_exists_works(): void
    {
        $this->blobClient->upload("test");

        self::assertTrue($this->blobClient->exists());

        $this->blobClient->deleteIfExists();

        self::assertFalse($this->blobClient->exists());
    }

    public function delete_if_exists_throws_if_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->blobClient->deleteIfExists();
    }

    #[Test]
    public function delete_if_exists_doesnt_throws_if_blob_doesnt_exist(): void
    {
        $this->expectNotToPerformAssertions();

        $this->blobClient->deleteIfExists();
    }

    #[Test]
    public function exists_works(): void
    {
        self::assertFalse($this->blobClient->exists());

        $this->blobClient->upload("test");

        self::assertTrue($this->blobClient->exists());
    }

    #[Test]
    public function exists_works_throws_if_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient('noop')->getBlobClient('noop')->exists();
    }

    #[Test]
    public function upload_works_with_single_upload(): void
    {
        FileFactory::withStream(1000, function (StreamInterface $file) {
            $beforeUploadContent = $file->getContents();
            $file->rewind();

            $this->blobClient->upload($file, new UploadBlobOptions("text/plain", initialTransferSize: 2000));

            $properties = $this->blobClient->getProperties();

            self::assertEquals("text/plain", $properties->contentType);
            self::assertEquals(1000, $properties->contentLength);

            $afterUploadContent = $this->blobClient->downloadStreaming()->content;

            self::assertEquals($beforeUploadContent, $afterUploadContent);
        });
    }

    #[Test]
    public function upload_works_with_parallel_upload(): void
    {
        FileFactory::withStream(1000, function (StreamInterface $file) {
            $beforeUploadContent = $file->getContents();
            $file->rewind();

            $this->blobClient->upload($file, new UploadBlobOptions("text/plain", initialTransferSize: 500, maximumTransferSize: 100));

            $properties = $this->blobClient->getProperties();

            self::assertEquals("text/plain", $properties->contentType);
            self::assertEquals(1000, $properties->contentLength);

            $blob = $this->blobClient->downloadStreaming();

            self::assertEquals($beforeUploadContent, $blob->content);
            self::assertEquals(md5($beforeUploadContent), $blob->properties->contentMD5);
        });
    }

    #[Test]
    public function upload_works_with_unknown_sized_stream(): void
    {
        FileFactory::withStream(1000, function (StreamInterface $file) {
            $stream = new class ($file) implements StreamInterface {
                use StreamDecoratorTrait;

                public function detach()
                {
                    return null;
                }

                public function getSize(): ?int
                {
                    return null;
                }
            };

            $beforeUploadContent = $file->getContents();
            $file->rewind();

            $this->blobClient->upload($stream, new UploadBlobOptions("text/plain", initialTransferSize: 500, maximumTransferSize: 100));

            $properties = $this->blobClient->getProperties();

            self::assertEquals("text/plain", $properties->contentType);
            self::assertEquals(1000, $properties->contentLength);

            $blob = $this->blobClient->downloadStreaming();

            self::assertEquals($beforeUploadContent, $blob->content);
            self::assertEquals(md5($beforeUploadContent), $blob->properties->contentMD5);
        });
    }

    #[Test]
    public function upload_works_with_non_seekable_stream(): void
    {
        FileFactory::withStream(1000, function (StreamInterface $file) {
            $stream = new class (new NoSeekStream($file)) implements StreamInterface {
                use StreamDecoratorTrait;

                public function detach()
                {
                    return null;
                }
            };

            $beforeUploadContent = $file->getContents();
            $file->rewind();

            $this->blobClient->upload($stream, new UploadBlobOptions("text/plain", initialTransferSize: 500, maximumTransferSize: 100));

            $properties = $this->blobClient->getProperties();

            self::assertEquals("text/plain", $properties->contentType);
            self::assertEquals(1000, $properties->contentLength);

            $blob = $this->blobClient->downloadStreaming();

            self::assertEquals($beforeUploadContent, $blob->content);
            self::assertEquals(md5($beforeUploadContent), $blob->properties->contentMD5);
        });
    }

    #[Test]
    public function upload_works_with_empty_file(): void
    {
        $this->blobClient->upload("", new UploadBlobOptions("text/plain", initialTransferSize: 500, maximumTransferSize: 100));

        $properties = $this->blobClient->getProperties();

        self::assertEquals("text/plain", $properties->contentType);
        self::assertEquals(0, $properties->contentLength);

        $afterUploadContent = $this->blobClient->downloadStreaming()->content;

        self::assertEquals("", $afterUploadContent);
    }

    #[Test]
    public function upload_throws_if_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient('noop')->getBlobClient('noop')->upload("test");
    }

    #[Test]
    public function sync_copy_from_url_works(): void
    {
        $sourceContainerClient = $this->serviceClient->getContainerClient($this->randomContainerName());

        $this->cleanContainer($sourceContainerClient->containerName);

        $sourceBlobClient = $sourceContainerClient->getBlobClient("to_copy");
        $sourceBlobClient->upload("This should be copied!");
        $sourceSas = $sourceBlobClient->generateSasUri(
            BlobSasBuilder::new()
                ->setPermissions(new BlobSasPermissions(read: true))
                ->setExpiresOn((new \DateTime())->modify("+ 1min")),
        );

        $result = $this->blobClient->syncCopyFromUri($sourceSas);

        self::assertEquals(CopyStatus::SUCCESS, $result->copyStatus);

        $sourceContent = $sourceBlobClient->downloadStreaming()->content->getContents();
        $targetContent = $this->blobClient->downloadStreaming()->content->getContents();

        self::assertEquals($targetContent, $sourceContent);
    }

    #[Test]
    public function sync_copy_from_url_throws_if_source_container_doesnt_exist(): void
    {
        $sourceContainerClient = $this->serviceClient->getContainerClient($this->randomContainerName());
        $sourceContainerClient->deleteIfExists(); // cleanup

        $sourceBlobClient = $sourceContainerClient->getBlobClient("to_copy");
        $sourceSas = $sourceBlobClient->generateSasUri(
            BlobSasBuilder::new()
                ->setPermissions(new BlobSasPermissions(read: true))
                ->setExpiresOn((new \DateTime())->modify("+ 1min")),
        );

        // somehow azurite doesn't throw the expected exception
        if ($this->isUsingSimulator()) {
            $this->expectException(ContainerNotFoundException::class);
        } else {
            $this->expectException(CannotVerifyCopySourceException::class);
        }

        $this->blobClient->syncCopyFromUri($sourceSas);
    }

    #[Test]
    public function sync_copy_from_url_works_throws_if_source_blob_doesnt_exist(): void
    {
        $sourceContainerClient = $this->serviceClient->getContainerClient($this->randomContainerName());

        $this->cleanContainer($sourceContainerClient->containerName);

        $sourceBlobClient = $sourceContainerClient->getBlobClient("to_copy");

        // somehow azurite doesn't throw the expected exception
        if ($this->isUsingSimulator()) {
            $this->expectException(BlobNotFoundException::class);
        } else {
            $this->expectException(CannotVerifyCopySourceException::class);
        }

        $this->blobClient->syncCopyFromUri($sourceBlobClient->uri);
    }

    #[Test]
    public function start_copy_from_url_works(): void
    {
        $sourceContainerClient = $this->serviceClient->getContainerClient($this->randomContainerName());

        $this->cleanContainer($sourceContainerClient->containerName);

        $sourceBlobClient = $sourceContainerClient->getBlobClient("to_copy");
        $sourceBlobClient->upload('This should be copied!');
        $sourceSas = $sourceBlobClient->generateSasUri(
            BlobSasBuilder::new()
                ->setPermissions(new BlobSasPermissions(read: true))
                ->setExpiresOn((new \DateTime())->modify("+ 1min")),
        );

        $this->blobClient->startCopyFromUri($sourceSas);

        // this might finish sync or async, but we can't check for a specific behaviour

        self::assertTrue($this->blobClient->getProperties()->copyStatus !== null);
    }

    #[Test]
    public function start_copy_from_url_throws_if_source_blob_doesnt_exist(): void
    {
        $sourceContainerClient = $this->serviceClient->getContainerClient($this->randomContainerName());

        $this->cleanContainer($sourceContainerClient->containerName);

        $sourceBlobClient = $sourceContainerClient->getBlobClient("to_copy");

        $this->expectException(BlobNotFoundException::class);

        $this->blobClient->startCopyFromUri($sourceBlobClient->uri);
    }

    #[Test]
    public function abort_copy_from_url_works(): void
    {
        // found no reliable way to test this, because the copy operation is too fast
        // this depends on the blob server load

        self::markTestSkipped();
    }

    #[Test]
    public function abort_copy_from_url_throws_if_copy_id_doesnt_exist(): void
    {
        $sourceContainerClient = $this->serviceClient->getContainerClient($this->randomContainerName());

        $this->cleanContainer($sourceContainerClient->containerName);

        $sourceBlobClient = $sourceContainerClient->getBlobClient("to_copy");
        $sourceBlobClient->upload("This should be copied!");
        $sourceSas = $sourceBlobClient->generateSasUri(
            BlobSasBuilder::new()
                ->setPermissions(new BlobSasPermissions(read: true))
                ->setExpiresOn((new \DateTime())->modify("+ 1min")),
        );

        $result = $this->blobClient->syncCopyFromUri($sourceSas);

        $this->expectException(NoPendingCopyOperationException::class);

        $this->blobClient->abortCopyFromUri($result->copyId);
    }

    #[Test]
    public function can_generate_sas_uri_works(): void
    {
        $containerClient = new BlobClient(new Uri("https://testing.blob.core.windows.net/testing/some-blob"));

        self::assertFalse($containerClient->canGenerateSasUri());

        $containerClient = new BlobClient(
            new Uri("https://testing.blob.core.windows.net/testing/some-blob"),
            new StorageSharedKeyCredential("noop", "noop"),
        );

        self::assertTrue($containerClient->canGenerateSasUri());
    }


    #[Test]
    public function generate_sas_uri_works(): void
    {
        $this->expectNotToPerformAssertions();

        $blobClient = $this->containerClient->getBlobClient("blob");
        $blobClient->upload("test");

        $sas = $blobClient->generateSasUri(
            BlobSasBuilder::new()
                ->setPermissions(new BlobSasPermissions(read: true))
                ->setExpiresOn((new \DateTime())->modify("+ 1min")),
        );

        $sasBlobClient = new BlobClient($sas);

        $sasBlobClient->downloadStreaming();
    }

    #[Test]
    public function set_tags_works(): void
    {
        $this->blobClient->upload("");
        $this->blobClient->setTags(['foo' => 'bar', 'baz' => 'boo']);

        $tags = $this->blobClient->getTags();

        self::assertEquals($tags['foo'], 'bar');
        self::assertEquals($tags['baz'], 'boo');
    }

    #[Test]
    public function set_tags_throws_when_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient("noop")->getBlobClient("noop")->setTags(['foo' => 'bar']);
    }

    #[Test]
    public function set_tags_throws_if_blob_doesnt_exist(): void
    {
        $this->expectException(BlobNotFoundException::class);

        $this->blobClient->setTags(['foo' => 'bar']);
    }

    #[Test]
    public function set_tags_throws_when_tag_key_is_too_large(): void
    {
        $this->expectException(TagsTooLargeException::class);

        $this->blobClient->setTags([str_pad("", 1000, 'a') => 'noop']);
    }

    #[Test]
    public function set_tags_throws_when_tag_value_is_too_large(): void
    {
        $this->expectException(TagsTooLargeException::class);

        $this->blobClient->setTags(["noop" => str_pad("", 1000, 'a')]);
    }

    #[Test]
    public function set_tags_throws_when_too_many_tags_are_provided(): void
    {
        $this->expectException(TagsTooLargeException::class);

        $tags = [];

        for ($i = 0; $i < 1000; $i++) {
            $tags["tag-$i"] = "noop";
        }

        $this->blobClient->setTags($tags);
    }

    #[Test]
    public function get_tags_works(): void
    {
        $this->blobClient->upload("");
        $this->blobClient->setTags(['foo' => 'bar', 'baz' => 'boo']);

        $tags = $this->blobClient->getTags();

        self::assertEquals($tags['foo'], 'bar');
        self::assertEquals($tags['baz'], 'boo');
    }

    #[Test]
    public function get_tags_throws_when_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient("noop")->getBlobClient("noop")->getTags();
    }

    #[Test]
    public function get_tags_throws_if_blob_doesnt_exist(): void
    {
        $this->expectException(BlobNotFoundException::class);

        $this->blobClient->getTags();
    }

    #[Test]
    public function set_metadata_works(): void
    {
        $this->blobClient->upload("");
        $props = $this->blobClient->getProperties();

        self::assertEmpty($props->metadata);

        $this->blobClient->setMetadata(['foo' => 'bar', 'baz' => 'qaz']);

        $props = $this->blobClient->getProperties();

        self::assertEquals('bar', $props->metadata['foo']);
        self::assertEquals('qaz', $props->metadata['baz']);
    }

    #[Test]
    public function set_metadata_throws_when_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient("noop")->getBlobClient("noop")->setMetadata(["foo" => "bar"]);
    }

    #[Test]
    public function set_metadata_throws_if_blob_doesnt_exist(): void
    {
        $this->expectException(BlobNotFoundException::class);

        $this->containerClient->getBlobClient("noop")->setMetadata(["foo" => "bar"]);
    }

    #[Test]
    public function getting_and_setting_http_headers_works(): void
    {
        $originalContent = "Hello, World!";
        $compressedContent = gzcompress($originalContent);
        if ($compressedContent === false) {
            self::fail("Failed to compress content");
        }

        $this->blobClient->upload(
            $compressedContent,
            new UploadBlobOptions(httpHeaders: new BlobHttpHeaders(
                cacheControl: "immutable",
                contentDisposition: "inline",
                contentEncoding: "gzip",
                contentHash: md5($compressedContent, true),
                contentLanguage: "en",
                contentType: "text/plain",
            )),
        );

        $properties = $this->blobClient->getProperties();

        self::assertEquals("text/plain", $properties->contentType);
        self::assertEquals("immutable", $properties->cacheControl);
        self::assertEquals("inline", $properties->contentDisposition);
        self::assertEquals("en", $properties->contentLanguage);
        self::assertEquals("gzip", $properties->contentEncoding);

        // The content is automatically decompressed when downloaded
        self::assertEquals($originalContent, $this->blobClient->downloadStreaming()->content->getContents());
    }

    #[Test]
    public function set_http_headers_works(): void
    {
        // Upload with initial content type
        $this->blobClient->upload("test content", new UploadBlobOptions(httpHeaders: new BlobHttpHeaders(
            contentType: "application/octet-stream"
        )));

        $initialProps = $this->blobClient->getProperties();
        self::assertEquals("application/octet-stream", $initialProps->contentType);

        // Change content type using setHttpHeaders
        $this->blobClient->setHttpHeaders(new BlobHttpHeaders(
            contentType: "text/plain",
            cacheControl: "public, max-age=3600"
        ));

        $updatedProps = $this->blobClient->getProperties();
        self::assertEquals("text/plain", $updatedProps->contentType);
        self::assertEquals("public, max-age=3600", $updatedProps->cacheControl);
    }

    #[Test]
    public function set_http_headers_after_copy_works(): void
    {
        // Create source blob
        $sourceBlobClient = $this->containerClient->getBlobClient("source.txt");
        $sourceBlobClient->upload("content to copy", new UploadBlobOptions(httpHeaders: new BlobHttpHeaders(
            contentType: "application/octet-stream"
        )));

        // Copy to target
        $targetBlobClient = $this->containerClient->getBlobClient("target.txt");
        $copyResult = $targetBlobClient->syncCopyFromUri($sourceBlobClient->uri);

        self::assertEquals(CopyStatus::SUCCESS, $copyResult->copyStatus);

        // Verify copied content type
        $copiedProps = $targetBlobClient->getProperties();
        self::assertEquals("application/octet-stream", $copiedProps->contentType);

        // Update properties on copied blob
        $targetBlobClient->setHttpHeaders(new BlobHttpHeaders(
            contentType: "image/jpeg"
        ));

        $updatedProps = $targetBlobClient->getProperties();
        self::assertEquals("image/jpeg", $updatedProps->contentType);
    }

    #[Test]
    public function set_http_headers_throws_if_blob_doesnt_exist(): void
    {
        $this->expectException(BlobNotFoundException::class);

        $this->blobClient->setHttpHeaders(new BlobHttpHeaders(
            contentType: "text/plain"
        ));
    }

    #[Test]
    public function set_http_headers_throws_if_container_doesnt_exist(): void
    {
        $this->expectException(ContainerNotFoundException::class);

        $this->serviceClient->getContainerClient("noop")->getBlobClient("noop")->setHttpHeaders(
            new BlobHttpHeaders(contentType: "text/plain")
        );
    }
}
