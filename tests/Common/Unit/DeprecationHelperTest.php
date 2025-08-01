<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Common\Unit;

use AzureOss\Storage\Blob\Helpers\DeprecationHelper;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;

class DeprecationHelperTest extends TestCase
{
    #[IgnoreDeprecations]
    #[WithoutErrorHandler]
    #[Test]
    public function calling_deprecated_public_constructor_triggers_deprecation_if_called_from_outside_of_the_class(): void
    {
        set_error_handler(
            function (int $errNo, string $errstr) {
                self::assertEquals('The constructor of AzureOss\Storage\Tests\Common\Unit\ClassWithDeprecatedPublicConstructor will be private in version 2.0.', $errstr);

                return false;
            },
            E_USER_DEPRECATED,
        );


        new ClassWithDeprecatedPublicConstructor();
    }

    #[WithoutErrorHandler]
    #[Test]
    public function calling_deprecated_public_constructor_doesnt_trigger_deprecation_if_called_from_inside_of_the_class(): void
    {
        $this->expectNotToPerformAssertions();

        set_error_handler(
            function () {
                self::fail('Deprecation triggered');
            },
            E_USER_DEPRECATED,
        );

        ClassWithDeprecatedPublicConstructor::new();

        restore_error_handler();
    }

    #[WithoutErrorHandler]
    #[Test]
    public function calling_deprecated_method_triggers_deprecation(): void
    {
        set_error_handler(
            function (int $errNo, string $errstr) {
                self::assertEquals('The method AzureOss\Storage\Tests\Common\Unit\ClassWithDeprecatedPublicMethod::deprecatedMethod() will be removed in version 2.0.', $errstr);

                return false;
            },
        );

        (new ClassWithDeprecatedPublicMethod())->deprecatedMethod();

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

class ClassWithDeprecatedPublicMethod
{
    public function deprecatedMethod(): void
    {
        DeprecationHelper::methodWillBeRemoved(self::class, __FUNCTION__, '2.0');
    }
}
