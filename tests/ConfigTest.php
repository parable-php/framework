<?php declare(strict_types=1);

namespace Parable\Framework\Tests;

use Parable\Framework\Config;
use Parable\GetSet\Resource\LocalResourceInterface;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testConfig(): void
    {
        $config = new Config();

        self::assertInstanceOf(LocalResourceInterface::class, $config);

        $config->set('test', true);

        self::assertTrue($config->get('test'));
        self::assertNull($config->get('nope'));
    }
}
