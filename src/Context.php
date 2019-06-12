<?php declare(strict_types=1);

namespace Parable\Framework;

class Context
{
    protected static $isCli;

    public static function isCli(): bool
    {
        return self::$isCli ?? PHP_SAPI === 'cli';
    }

    public static function isHttp(): bool
    {
        return self::isCli() === false;
    }

    public static function clearIsCliForTest(): void
    {
        self::$isCli = null;
    }

    public static function setIsCliForTest(bool $isCli): void
    {
        if (!defined('RUNNING_IN_TEST')) {
            throw new Exception('Cannot set context return value outside of tests.'); // @codeCoverageIgnore
        }

        self::$isCli = $isCli;
    }
}
