<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Blob\Exceptions\AuthorizationFailedException;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthorizationTest extends BlobFeatureTestCase
{
    #[Test]
    public function throw_exception_when_unauthorized(): void
    {
        $this->expectException(AuthorizationFailedException::class);

        $badClient = new BlobServiceClient($this->serviceClient->blobEndpoint, new StorageSharedKeyCredential('noop', 'noop'));

        $badClient->getContainerClient('test')->create();
    }
}
