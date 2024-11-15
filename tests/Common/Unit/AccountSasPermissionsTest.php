<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Common\Unit;

use AzureOss\Storage\Common\Sas\AccountSasPermissions;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AccountSasPermissionsTest extends TestCase
{
    #[Test]
    public function to_string_works(): void
    {
        $permissions = new AccountSasPermissions();

        self::assertEquals("", (string) $permissions);

        $permissions = new AccountSasPermissions(read: true, delete: true, add: true);

        self::assertEquals("rda", (string) $permissions);

        $permissions = new AccountSasPermissions(
            read: true,
            write: true,
            delete: true,
            permanentDelete: true,
            list: true,
            add: true,
            create: true,
            update: true,
            process: true,
            tags: true,
            filter: true,
            setImmutabilityPolicy: true,
        );

        self::assertEquals("rwdylacuptfi", (string) $permissions);
    }
}
