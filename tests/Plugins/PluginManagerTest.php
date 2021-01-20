<?php declare(strict_types=1);

namespace Parable\Framework\Tests\Plugins;

use Parable\Framework\Config;
use Parable\Framework\ConsoleApplication;
use Parable\Framework\Context;
use Parable\Framework\Exception;
use Parable\Framework\Plugins\PluginManager;
use Parable\Framework\Tests\AbstractTestCase;
use Parable\Framework\Tests\Classes\CliPluginImplementation;
use Parable\Framework\Tests\Classes\PluginImplementation;
use Parable\Framework\Tests\Classes\HttpPluginImplementation;
use Parable\Routing\Router;

class PluginManagerTest extends AbstractTestCase
{
    /**
     * @var PluginManager
     */
    protected $pluginManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->pluginManager = new class extends PluginManager
        {
            public static function clearPlugins(): void
            {
                static::$pluginClassNames = [];
            }
        };

        $this->pluginManager::clearPlugins();
    }

    public function testPluginManagerDoesNothingWithoutPluginsForTimeSlot(): void
    {
        /*
         * The only way to test this is to see if the only guaranteed dependency
         * is instantiated or not -- Config
         */
        self::assertFalse($this->container->has(Config::class));

        $this->pluginManager::startPlugins('now', $this->container);

        self::assertFalse($this->container->has(Config::class));
    }

    public function testPluginManagerHandlesPlugins(): void
    {
        $this->pluginManager::addPlugin('now', PluginImplementation::class);

        self::assertFalse($this->container->has(Config::class));

        $this->pluginManager::startPlugins('now', $this->container);

        self::assertTrue($this->container->has(Config::class));

        $dependencies = $this->container->get(Config::class)->get('dependencies');

        self::assertCount(1, $dependencies);

        [$config] = $dependencies;

        self::assertInstanceOf(Config::class, $config);
    }

    public function testPluginWithoutValidInterfaceIsNotAccepted(): void
    {
        $this->expectExceptionMessage("Plugin 'Parable\Framework\Config' does not implement PluginInterface");
        $this->expectException(Exception::class);

        $this->pluginManager::addPlugin('now', Config::class);
        $this->pluginManager::startPlugins('now', $this->container);
    }
}
