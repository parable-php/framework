<?php declare(strict_types=1);

namespace Parable\Framework\Tests;

use Parable\Framework\Path;

class PathTest extends AbstractTestCase
{
    protected Path $path;

    public function setUp(): void
    {
        $this->path = new Path();

        parent::setUp();
    }

    public function testBasePathInstance(): void
    {
        self::assertSame(BASEDIR, $this->path->getRoot());
        self::assertSame(BASEDIR . '/subdir', $this->path->getPath('subdir'));
        self::assertSame(BASEDIR . '/subdir', $this->path->getPath('subdir/'));
        self::assertSame(BASEDIR . '/subdir', $this->path->getPath('/subdir'));
        self::assertSame(BASEDIR . '/subdir', $this->path->getPath('/subdir/'));
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
