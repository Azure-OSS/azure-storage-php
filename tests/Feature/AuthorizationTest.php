<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Feature;

use AzureOss\Storage\Auth\SharedKeyAuthScheme;
use AzureOss\Storage\BlobApiClient;
use AzureOss\Storage\Exceptions\AuthorizationFailedException;
use AzureOss\Storage\StorageServiceSettings;
use AzureOss\Storage\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthorizationTest extends FeatureTestCase
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
