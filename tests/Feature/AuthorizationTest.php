<?php

declare(strict_types=1);

namespace Brecht\FlysystemAzureBlobStorage\Tests\Feature;

use Brecht\FlysystemAzureBlobStorage\Auth\SharedKeyAuthScheme;
use Brecht\FlysystemAzureBlobStorage\BlobApiClient;
use Brecht\FlysystemAzureBlobStorage\Exceptions\AuthorizationFailedException;
use Brecht\FlysystemAzureBlobStorage\StorageServiceSettings;
use Brecht\FlysystemAzureBlobStorage\Tests\FeatureTestCase;
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
