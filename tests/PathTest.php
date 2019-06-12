<?php declare(strict_types=1);

namespace Parable\Framework\Tests;

use Parable\Framework\Path;

class PathTest extends AbstractTestCase
{
    public function testBasePathInstance(): void
    {
        $path = new Path();

        self::assertSame(BASEDIR, $path->getRoot());
        self::assertSame(BASEDIR . '/subdir', $path->getPath('subdir'));
        self::assertSame(BASEDIR . '/subdir', $path->getPath('subdir/'));
        self::assertSame(BASEDIR . '/subdir', $path->getPath('/subdir'));
        self::assertSame(BASEDIR . '/subdir', $path->getPath('/subdir/'));
    }

    public function testCustomPathInstance(): void
    {
        $path = new Path('/var/www');

        self::assertSame('/var/www', $path->getRoot());
        self::assertSame('/var/www/subdir', $path->getPath('subdir'));
        self::assertSame('/var/www/subdir', $path->getPath('subdir/'));
        self::assertSame('/var/www/subdir', $path->getPath('/subdir'));
        self::assertSame('/var/www/subdir', $path->getPath('/subdir/'));
    }
}
