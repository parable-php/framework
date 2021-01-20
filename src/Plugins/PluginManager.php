<?php declare(strict_types=1);

namespace Parable\Framework\Plugins;

use Parable\Di\Container;
use Parable\Framework\Exception;

class PluginManager
{
    /** @var string[][]|PluginInterface[][] */
    protected static array $pluginClassNames = [];

    public static function addPlugin(string $trigger, string $pluginClassName): void
    {
        self::$pluginClassNames[$trigger][] = $pluginClassName;
    }

    public static function startPlugins(string $timeSlot, Container $container): void
    {
        if (!array_key_exists($timeSlot, static::$pluginClassNames)) {
            return;
        }

        foreach (self::$pluginClassNames[$timeSlot] as $pluginClassName) {
            $plugin = $container->get($pluginClassName);

            if (!($plugin instanceof PluginInterface)) {
                throw new Exception(sprintf(
                    "Plugin '%s' does not implement PluginInterface",
                    $pluginClassName
                ));
            }

            $plugin->run();
        }
    }
}
