<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Common\Unit;

use AzureOss\Storage\Common\Sas\SasIpRange;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SasIpRangeTest extends TestCase
{
    #[Test]
    public function to_string_works_with_start_and_end(): void
    {
        $ipRange = new SasIpRange("0.0.0.0", "255.255.255.255");

        self::assertEquals("0.0.0.0-255.255.255.255", (string) $ipRange);
    }

    #[Test]
    public function to_string_works_with_only_start(): void
    {
        $ipRange = new SasIpRange("192.168.0.1");

        self::assertEquals("192.168.0.1", (string) $ipRange);
    }
}
