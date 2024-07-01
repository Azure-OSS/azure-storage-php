<?php

declare(strict_types=1);

namespace AzureOss\Storage\Interfaces;

use AzureOss\Storage\Exceptions\BlobNotFoundException;
use AzureOss\Storage\Exceptions\ContainerAlreadyExistsException;
use AzureOss\Storage\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Requests\Block;
use AzureOss\Storage\Requests\CreateContainerOptions;
use AzureOss\Storage\Requests\DeleteBlobOptions;
use AzureOss\Storage\Requests\DeleteContainerOptions;
use AzureOss\Storage\Requests\GetBlobOptions;
use AzureOss\Storage\Requests\GetBlobPropertiesOptions;
use AzureOss\Storage\Requests\GetContainerPropertiesOptions;
use AzureOss\Storage\Requests\ListBlobsOptions;
use AzureOss\Storage\Requests\PutBlobOptions;
use AzureOss\Storage\Requests\PutBlockListOptions;
use AzureOss\Storage\Requests\PutBlockOptions;
use AzureOss\Storage\Requests\UploadBlockBlobOptions;
use AzureOss\Storage\Responses\CreateContainerResponse;
use AzureOss\Storage\Responses\DeleteBlobResponse;
use AzureOss\Storage\Responses\DeleteContainerResponse;
use AzureOss\Storage\Responses\GetBlobPropertiesResponse;
use AzureOss\Storage\Responses\GetBlobResponse;
use AzureOss\Storage\Responses\GetContainerPropertiesResponse;
use AzureOss\Storage\Responses\ListBlobsResponse;
use AzureOss\Storage\Responses\PutBlobResponse;
use AzureOss\Storage\Responses\PutBlockListResponse;
use AzureOss\Storage\Responses\PutBlockResponse;
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
