<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Common\Unit;

use AzureOss\Storage\Common\Sas\AccountSasServices;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AccountSasServicesTest extends TestCase
{
    #[Test]
    public function to_string_works(): void
    {
        $services = new AccountSasServices();

        self::assertEquals("", (string) $services);

        $services = new AccountSasServices(queue: true);

        self::assertEquals("q", (string) $services);

        $services = new AccountSasServices(blob: true, queue: true, table: true, file: true);

        self::assertEquals("bqtf", (string) $services);
    }
}
