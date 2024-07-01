<?php

declare(strict_types=1);

namespace Brecht\FlysystemAzureBlobStorage\Interfaces;

use Brecht\FlysystemAzureBlobStorage\Exceptions\BlobNotFoundException;
use Brecht\FlysystemAzureBlobStorage\Exceptions\ContainerAlreadyExistsException;
use Brecht\FlysystemAzureBlobStorage\Exceptions\ContainerNotFoundException;
use Brecht\FlysystemAzureBlobStorage\Requests\Block;
use Brecht\FlysystemAzureBlobStorage\Requests\CreateContainerOptions;
use Brecht\FlysystemAzureBlobStorage\Requests\DeleteBlobOptions;
use Brecht\FlysystemAzureBlobStorage\Requests\DeleteContainerOptions;
use Brecht\FlysystemAzureBlobStorage\Requests\GetBlobOptions;
use Brecht\FlysystemAzureBlobStorage\Requests\GetBlobPropertiesOptions;
use Brecht\FlysystemAzureBlobStorage\Requests\GetContainerPropertiesOptions;
use Brecht\FlysystemAzureBlobStorage\Requests\ListBlobsOptions;
use Brecht\FlysystemAzureBlobStorage\Requests\PutBlobOptions;
use Brecht\FlysystemAzureBlobStorage\Requests\PutBlockListOptions;
use Brecht\FlysystemAzureBlobStorage\Requests\PutBlockOptions;
use Brecht\FlysystemAzureBlobStorage\Requests\UploadBlockBlobOptions;
use Brecht\FlysystemAzureBlobStorage\Responses\CreateContainerResponse;
use Brecht\FlysystemAzureBlobStorage\Responses\DeleteBlobResponse;
use Brecht\FlysystemAzureBlobStorage\Responses\DeleteContainerResponse;
use Brecht\FlysystemAzureBlobStorage\Responses\GetBlobPropertiesResponse;
use Brecht\FlysystemAzureBlobStorage\Responses\GetBlobResponse;
use Brecht\FlysystemAzureBlobStorage\Responses\GetContainerPropertiesResponse;
use Brecht\FlysystemAzureBlobStorage\Responses\ListBlobsResponse;
use Brecht\FlysystemAzureBlobStorage\Responses\PutBlobResponse;
use Brecht\FlysystemAzureBlobStorage\Responses\PutBlockListResponse;
use Brecht\FlysystemAzureBlobStorage\Responses\PutBlockResponse;
use Psr\Http\Message\StreamInterface;

interface BlobClient
{
    /**
     * @param  resource|string|StreamInterface  $content
     *
     * @throws ContainerNotFoundException
     * @throws BlobNotFoundException
     */
    public function uploadBlockBlob(string $container, string $blob, $content, ?UploadBlockBlobOptions $options = null): void;

    public function containerExists(string $container): bool;

    /**
     * @throws ContainerNotFoundException
     */
    public function blobExists(string $container, string $blob): bool;

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/create-container
     *
     * @throws ContainerAlreadyExistsException
     */
    public function createContainer(string $container, ?CreateContainerOptions $options = null): CreateContainerResponse;

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/get-container-properties
     *
     * @throws ContainerNotFoundException
     */
    public function getContainerProperties(string $container, ?GetContainerPropertiesOptions $options = null): GetContainerPropertiesResponse;

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/delete-container
     *
     * @throws ContainerNotFoundException
     */
    public function deleteContainer(string $container, ?DeleteContainerOptions $options = null): DeleteContainerResponse;

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/list-blobs
     *
     * @throws ContainerNotFoundException
     * @throws BlobNotFoundException
     */
    public function listBlobs(string $container, ?ListBlobsOptions $options = null): ListBlobsResponse;

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/put-blob?tabs=microsoft-entra-id
     *
     * @param  resource|string|StreamInterface  $content
     *
     * @throws ContainerNotFoundException
     * @throws BlobNotFoundException
     */
    public function putBlob(string $container, string $blob, $content, ?PutBlobOptions $options = null): PutBlobResponse;

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/put-block
     *
     * @param  resource|string  $content
     *
     * @throws ContainerNotFoundException
     * @throws BlobNotFoundException
     */
    public function putBlock(string $container, string $blob, Block $block, $content, ?PutBlockOptions $options = null): PutBlockResponse;

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/put-block-list
     *
     * @param  Block[]  $blocks
     *
     * @throws ContainerNotFoundException
     * @throws BlobNotFoundException
     */
    public function putBlockList(string $container, string $blob, array $blocks, ?PutBlockListOptions $options = null): PutBlockListResponse;

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/get-blob
     *
     * @throws ContainerNotFoundException
     * @throws BlobNotFoundException
     */
    public function getBlob(string $container, string $blob, ?GetBlobOptions $options = null): GetBlobResponse;

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/get-blob-properties
     *
     * @throws ContainerNotFoundException
     * @throws BlobNotFoundException
     */
    public function getBlobProperties(string $container, string $blob, ?GetBlobPropertiesOptions $options = null): GetBlobPropertiesResponse;

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/delete-blob
     *
     * @throws ContainerNotFoundException
     * @throws BlobNotFoundException
     */
    public function deleteBlob(string $container, string $blob, ?DeleteBlobOptions $options = null): DeleteBlobResponse;
}
