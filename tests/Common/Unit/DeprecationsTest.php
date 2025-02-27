<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Common\Unit;

use AzureOss\Storage\Blob\Models\BlobPrefix;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DeprecationsTest extends TestCase
{
    #[Test]
    public function model_constructors_cause_deprecation_error_when_called_publicly(): void
    {
        new BlobPrefix('name');
    }
}
