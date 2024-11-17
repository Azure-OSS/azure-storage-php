<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Unit;

use AzureOss\Storage\Blob\Sas\BlobSasPermissions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BlobSasPermissionsTest extends TestCase
{
    #[Test]
    public function to_string_works(): void
    {
        $permissions = new BlobSasPermissions();

        self::assertEquals("", (string) $permissions);

        $permissions = new BlobSasPermissions(read: true, delete: true);

        self::assertEquals("rd", (string) $permissions);

        $permissions = new BlobSasPermissions(
            read: true,
            add: true,
            create: true,
            write: true,
            delete: true,
            deleteVersion: true,
            permanentDelete: true,
            tags: true,
            list: true,
            move: true,
            execute: true,
            ownership: true,
            permissions: true,
            setImmutabilityPolicy: true,
        );

        self::assertEquals("racwdxyltmeopi", (string) $permissions);
    }
}
