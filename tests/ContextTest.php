<?php declare(strict_types=1);

namespace Parable\Framework\Tests;

use Parable\Framework\Context;

class ContextTest extends AbstractTestCase
{
    public function testIsCli(): void
    {
        self::assertTrue(Context::isCli());
    }

    public function testIsHttp(): void
    {
        self::assertFalse(Context::isHttp());
    }

    public function testSetIsCli(): void
    {
        Context::setIsCliForTest(false);

        self::assertFalse(Context::isCli());
        self::assertTrue(Context::isHttp());

        Context::clearIsCliForTest();
    }
}
