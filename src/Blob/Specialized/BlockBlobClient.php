<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Specialized;

use AzureOss\Storage\Blob\Exceptions\BlobStorageExceptionDeserializer;
use AzureOss\Storage\Blob\Exceptions\InvalidBlobUriException;
use AzureOss\Storage\Blob\Helpers\BlobUriParserHelper;
use AzureOss\Storage\Blob\Helpers\HashHelper;
use AzureOss\Storage\Blob\Models\CommitBlockListOptions;
use AzureOss\Storage\Blob\Models\StageBlockOptions;
use AzureOss\Storage\Blob\Requests\PutBlockRequestBody;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Common\Auth\TokenCredential;
use AzureOss\Storage\Common\Middleware\ClientFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class BlockBlobClient
{
    private readonly Client $client;

    public readonly string $containerName;

    public readonly string $blobName;

    /**
     * @deprecated Use $credential instead.
     */
    public ?StorageSharedKeyCredential $sharedKeyCredential = null;

    /**
     * @throws InvalidBlobUriException
     */
    public function __construct(
        public readonly UriInterface $uri,
        public readonly StorageSharedKeyCredential|TokenCredential|null $credential = null,
    ) {
        $this->containerName = BlobUriParserHelper::getContainerName($uri);
        $this->blobName = BlobUriParserHelper::getBlobName($uri);
        $this->client = (new ClientFactory())->create($uri, $credential, new BlobStorageExceptionDeserializer());

        if ($credential instanceof StorageSharedKeyCredential) {
            /** @phpstan-ignore-next-line  */
            $this->sharedKeyCredential = $credential;
        }
    }

    public function stageBlock(string $base64BlockId, StreamInterface|string $content, ?StageBlockOptions $options = null): void
    {
        $this->stageBlockAsync($base64BlockId, $content, $options)->wait();
    }

    public function stageBlockAsync(string $base64BlockId, StreamInterface|string $content, ?StageBlockOptions $options = null): PromiseInterface
    {
        $stream = Utils::streamFor($content);

        $md5 = Utils::hash($stream, 'md5', true);

        return $this->client
            ->putAsync($this->uri, [
                RequestOptions::QUERY => [
                    'comp' => 'block',
                    'blockid' => $base64BlockId,
                ],
                RequestOptions::HEADERS => [
                    'Content-MD5' => HashHelper::serializeMd5($md5),
                    'Content-Length' => $stream->getSize(),
                ],
                'body' => $content,
            ]);
    }

    /**
     * @param string[] $base64BlockIds
     */
    public function commitBlockList(array $base64BlockIds, ?CommitBlockListOptions $options = null): void
    {
        $this->commitBlockListAsync($base64BlockIds, $options)->wait();
    }

    /**
     * @param string[] $base64BlockIds
     */
    public function commitBlockListAsync(array $base64BlockIds, ?CommitBlockListOptions $options = null): PromiseInterface
    {
        if ($options === null) {
            $options = new CommitBlockListOptions();
        }

        return $this->client
            ->putAsync($this->uri, [
                RequestOptions::QUERY => [
                    'comp' => 'blocklist',
                ],
                RequestOptions::HEADERS => array_filter([
                    'x-ms-blob-cache-control' => $options->httpHeaders->cacheControl,
                    'x-ms-blob-content-type' => $options->httpHeaders->contentType,
                    'x-ms-blob-content-encoding' => $options->httpHeaders->contentEncoding,
                    'x-ms-blob-content-language' => $options->httpHeaders->contentLanguage,
                    'x-ms-blob-content-md5' => $options->httpHeaders->contentHash !== null ? base64_encode($options->httpHeaders->contentHash) : null,
                    'x-ms-blob-content-disposition' => $options->httpHeaders->contentDisposition,
                ], fn($value) => $value !== null),
                'body' => (new PutBlockRequestBody($base64BlockIds))->toXml()->asXML(),
            ]);
    }
}
