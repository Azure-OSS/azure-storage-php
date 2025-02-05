<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

use AzureOss\Storage\Blob\BlobClient;
use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Blob\Models\UploadBlobOptions;
use AzureOss\Storage\Tests\Utils\FileFactory;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Server\Server;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class MockBlobClientTest extends TestCase
{
    private BlobClient $mockBlobClient;

    protected function setUp(): void
    {
        Server::start();

        /** @phpstan-ignore-next-line */
        $uri = new Uri(Server::$url . '/devstoreaccount1');
        $mockServiceClient = new BlobServiceClient($uri);
        $mockContainerClient = $mockServiceClient->getContainerClient('test');
        $this->mockBlobClient = $mockContainerClient->getBlobClient('test');
    }

    protected function tearDown(): void
    {
        Server::stop();
    }

    #[Test]
    public function upload_single_sends_correct_amount_of_requests(): void
    {
        Server::enqueue(array_fill(0, 1000, new Response(200)));

        FileFactory::withStream(1000, function (StreamInterface $file) {
            $this->mockBlobClient->upload($file, new UploadBlobOptions("text/plain", initialTransferSize: 2000));
        });

        self::assertCount(1, Server::received());
    }

    #[Test]
    public function upload_parallel_blocks_sends_correct_amount_of_requests(): void
    {
        Server::enqueue(array_fill(0, 1000, new Response(200)));

        FileFactory::withStream(50_000_000, function (StreamInterface $file) {
            $this->mockBlobClient->upload($file, new UploadBlobOptions("text/plain", initialTransferSize: 0, maximumTransferSize: 3_000_000));
        });

        self::assertCount(18, Server::received()); // 50kb in 3kb chunks => 17 requests + final request = 18
    }

    #[Test]
    public function upload_sequential_blocks_sends_correct_amount_of_requests(): void
    {
        Server::enqueue(array_fill(0, 1000, new Response(200)));

        FileFactory::withStream(50_000_000, function (StreamInterface $file) {
            $stream = new class ($file) implements StreamInterface {
                use StreamDecoratorTrait;

                public function getSize(): ?int
                {
                    return null;
                }
            };

            $this->mockBlobClient->upload($stream, new UploadBlobOptions("text/plain", initialTransferSize: 0, maximumTransferSize: 3_000_000));
        });

        self::assertCount(18, Server::received()); // 50kb in 3kb chunks => 17 requests + final request = 18
    }
}
