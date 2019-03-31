<?php declare(strict_types=1);

namespace Parable\Framework;

class Context
{
    public static function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    public static function isHttp(): bool
    {
        return self::isCli() === false;
    }
}
