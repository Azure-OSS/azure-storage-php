<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Common\Unit;

use AzureOss\Storage\Blob\Helpers\DeprecationHelper;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;

class DeprecationHelperTest extends TestCase
{
    #[IgnoreDeprecations]
    public function test_constructor_will_be_private_triggers_deprecation_if_called_from_outside_of_the_class(): void
    {
        $this->expectUserDeprecationMessage('The constructor of AzureOss\Storage\Tests\Common\Unit\ClassWithDeprecatedPublicConstructor will be private in version 2.0.');

        new ClassWithDeprecatedPublicConstructor();
    }

    #[WithoutErrorHandler]
    public function test_constructor_will_be_private_doesnt_trigger_deprecation_if_called_from_inside_of_the_class(): void
    {
        $this->expectNotToPerformAssertions();

        set_error_handler(fn() => self::fail('Deprecation triggered'), E_USER_DEPRECATED);

        ClassWithDeprecatedPublicConstructor::new();

        restore_error_handler();
    }
}

class ClassWithDeprecatedPublicConstructor
{
    public function __construct()
    {
        DeprecationHelper::constructorWillBePrivate(self::class, '2.0');
    }

    public static function new(): self
    {
        return new self();
    }
}
