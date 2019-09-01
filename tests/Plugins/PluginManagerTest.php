<?php declare(strict_types=1);

namespace Parable\Framework\Tests\Plugins;

use Parable\Framework\Config;
use Parable\Framework\ConsoleApplication;
use Parable\Framework\Context;
use Parable\Framework\Exception;
use Parable\Framework\Plugins\PluginManager;
use Parable\Framework\Tests\AbstractTestCase;
use Parable\Framework\Tests\Classes\CliPluginImplementation;
use Parable\Framework\Tests\Classes\GenericPluginImplementation;
use Parable\Framework\Tests\Classes\HttpPluginImplementation;
use Parable\Routing\Router;

class PluginManagerTest extends AbstractTestCase
{
    /**
     * @var PluginManager
     */
    protected $pluginManager;

    public function setUp()
    {
        parent::setUp();

        $this->pluginManager = new class extends PluginManager
        {
            public static function clearPlugins(): void
            {
                self::$pluginClassNames = [];
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

    public function testPluginManagerHandlesGenericPlugins(): void
    {
        $this->pluginManager::addPlugin('now', GenericPluginImplementation::class);

        self::assertFalse($this->container->has(Config::class));

        $this->pluginManager::startPlugins('now', $this->container);

        self::assertTrue($this->container->has(Config::class));

        $dependencies = $this->container->get(Config::class)->get('dependencies');

        self::assertCount(1, $dependencies);

        [$config] = $dependencies;

        self::assertInstanceOf(Config::class, $config);
    }

    public function testPluginManagerHandlesCliPlugins(): void
    {
        $this->pluginManager::addPlugin('now', CliPluginImplementation::class);

        self::assertFalse($this->container->has(Config::class));

        $this->pluginManager::startPlugins('now', $this->container);

        self::assertTrue($this->container->has(Config::class));

        $dependencies = $this->container->get(Config::class)->get('dependencies');

        self::assertCount(2, $dependencies);

        [$config, $application] = $dependencies;

        self::assertInstanceOf(Config::class, $config);
        self::assertInstanceOf(ConsoleApplication::class, $application);
    }

    public function testPluginManagerHandlesHttpPlugins(): void
    {
        Context::setIsCliForTest(false);

        $this->pluginManager::addPlugin('now', HttpPluginImplementation::class);

        self::assertFalse($this->container->has(Config::class));

        $this->pluginManager::startPlugins('now', $this->container);

        self::assertTrue($this->container->has(Config::class));

        $dependencies = $this->container->get(Config::class)->get('dependencies');

        self::assertCount(2, $dependencies);

        [$config, $router] = $dependencies;

        self::assertInstanceOf(Config::class, $config);
        self::assertInstanceOf(Router::class, $router);

        Context::clearIsCliForTest();
    }

    public function testPluginWithoutValidInterfaceIsNotAccepted(): void
    {
        $this->expectExceptionMessage("Plugin 'Parable\Framework\Config' does not implement a valid plugin interface (Parable\Framework\Plugins\CliPluginInterface, Parable\Framework\Plugins\GenericPluginInterface, Parable\Framework\Plugins\HttpPluginInterface)");
        $this->expectException(Exception::class);

        $this->pluginManager::addPlugin('now', Config::class);
        $this->pluginManager::startPlugins('now', $this->container);
    }
}
