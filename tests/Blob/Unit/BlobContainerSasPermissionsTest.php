<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Unit;

use AzureOss\Storage\Blob\Sas\BlobContainerSasPermissions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BlobContainerSasPermissionsTest extends TestCase
{
    #[Test]
    public function to_string_works(): void
    {
        $permissions = new BlobContainerSasPermissions();

        self::assertEquals("", (string) $permissions);

        $permissions = new BlobContainerSasPermissions(read: true, delete: true);

        self::assertEquals("rd", (string) $permissions);

        $permissions = new BlobContainerSasPermissions(
            read: true,
            add: true,
            create: true,
            write: true,
            delete: true,
            deleteVersion: true,
            list: true,
            find: true,
            move: true,
            execute: true,
            ownership: true,
            permissions: true,
            setImmutabilityPolicy: true,
        );

        self::assertEquals("racwdxlfmeopi", (string) $permissions);
    }
}
