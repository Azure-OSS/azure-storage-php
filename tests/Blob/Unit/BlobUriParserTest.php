<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Unit;

use AzureOss\Storage\Blob\Exceptions\InvalidBlobUriException;
use AzureOss\Storage\Blob\Helpers\BlobUriParserHelper;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BlobUriParserTest extends TestCase
{
    #[Test]
    public function get_container_name_works(): void
    {
        $uri = new Uri("https://testing.blob.core.windows.net/testing");

        $this->assertEquals("testing", BlobUriParserHelper::getContainerName($uri));
    }

    #[Test]
    public function get_container_name_works_for_dev_account(): void
    {
        $uri = new Uri("http://127.0.0.1:10000/devstoreaccount1/testing");

        $this->assertEquals("testing", BlobUriParserHelper::getContainerName($uri));
    }

    #[Test]
    public function get_container_throws_if_it_is_not_a_valid_blob_uri(): void
    {
        $uri = new Uri("http://127.0.0.1:10000");

        $this->expectException(InvalidBlobUriException::class);

        BlobUriParserHelper::getContainerName($uri);
    }

    #[Test]
    public function get_blob_name_works(): void
    {
        $uri = new Uri("https://testing.blob.core.windows.net/testing/file.txt");

        $this->assertEquals("file.txt", BlobUriParserHelper::getBlobName($uri));

        $uri = new Uri("https://testing.blob.core.windows.net/testing/some/file.txt");

        $this->assertEquals("some/file.txt", BlobUriParserHelper::getBlobName($uri));

        $uri = new Uri("https://testing.blob.core.windows.net/testing/some/deep/file.txt");

        $this->assertEquals("some/deep/file.txt", BlobUriParserHelper::getBlobName($uri));
    }

    #[Test]
    public function get_blob_name_works_for_dev_account(): void
    {
        $uri = new Uri("http://127.0.0.1:10000/devstoreaccount1/testing/file.txt");

        $this->assertEquals("file.txt", BlobUriParserHelper::getBlobName($uri));

        $uri = new Uri("http://127.0.0.1:10000/devstoreaccount1/testing/some/file.txt");

        $this->assertEquals("some/file.txt", BlobUriParserHelper::getBlobName($uri));

        $uri = new Uri("http://127.0.0.1:10000/devstoreaccount1/testing/some/deep/file.txt");

        $this->assertEquals("some/deep/file.txt", BlobUriParserHelper::getBlobName($uri));
    }

    #[Test]
    public function get_blob_name_throws_if_it_is_not_a_valid_blob_uri(): void
    {
        $uri = new Uri("http://127.0.0.1:10000");

        $this->expectException(InvalidBlobUriException::class);

        BlobUriParserHelper::getBlobName($uri);
    }

    #[Test]
    public function is_development_uri_works(): void
    {
        $uri = new Uri("http://127.0.0.1:10000/devstoreaccount1/testing");

        $this->assertTrue(BlobUriParserHelper::isDevelopmentUri($uri));

        $uri = new Uri("https://testing.blob.core.windows.net/testing/file.txt");

        $this->assertFalse(BlobUriParserHelper::isDevelopmentUri($uri));
    }
}
