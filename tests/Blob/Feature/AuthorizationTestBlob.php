<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Feature;

use AzureOss\Storage\Blob\BlobApiClient;
use AzureOss\Storage\Blob\Exceptions\AuthorizationFailedException;
use AzureOss\Storage\Common\Auth\SharedKeyAuthScheme;
use AzureOss\Storage\Common\StorageServiceSettings;
use AzureOss\Storage\Tests\Blob\BlobFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthorizationTestBlob extends BlobFeatureTestCase
{
    #[Test]
    public function throw_exception_when_unauthorized()
    {
        $this->expectException(AuthorizationFailedException::class);

        $settings = new StorageServiceSettings($this->client->settings->blobEndpoint, 'noop', 'noop');
        $auth = new SharedKeyAuthScheme($settings);

        $this->client = new BlobApiClient($settings, $auth);

        $this->client->createContainer('test');
    }
}
