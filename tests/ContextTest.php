<?php declare(strict_types=1);

namespace Parable\Framework\Tests;

use Parable\Framework\Context;

class ContextTest extends \PHPUnit\Framework\TestCase
{
    public function testIsCli(): void
    {
        self::assertTrue(Context::isCli());
    }

    public function testIsHttp(): void
    {
        self::assertFalse(Context::isHttp());
    }
}
