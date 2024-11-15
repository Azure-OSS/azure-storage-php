<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Common\Unit;

use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Common\Exceptions\InvalidAccountKeyException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StorageSharedKeyCredentialTest extends TestCase
{
    #[Test]
    public function compute_hmacs_sha256_works(): void
    {
        $credential = new StorageSharedKeyCredential(
            "devstoreaccount1",
            "Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==",
        );

        self::assertEquals("Vir+MCz8c2dq+mnnEDMDF3s6vDBzCyY6WRzlblvWsgw=", $credential->computeHMACSHA256("Hello, world!"));
    }

    #[Test]
    public function compute_hmacs_sha256_throws_when_account_key_is_invalid(): void
    {
        $this->expectException(InvalidAccountKeyException::class);

        $credential = new StorageSharedKeyCredential(
            "devstoreaccount1",
            "This_is_not_base64!",
        );

        $credential->computeHMACSHA256("Hello, world!");
    }
}
