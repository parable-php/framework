<?php declare(strict_types=1);

namespace Parable\Framework\Plugins;

use Parable\Di\Container;
use Parable\Framework\Config;
use Parable\Framework\ConsoleApplication;
use Parable\Framework\Context;
use Parable\Framework\Exception;
use Parable\Routing\Router;

class PluginManager
{
    protected static $acceptedPluginInterfaces = [
        CliPluginInterface::class,
        GenericPluginInterface::class,
        HttpPluginInterface::class,
    ];

    /**
     * @var string[][]|HttpPluginInterface[][]|CliPluginInterface[][]|GenericPluginInterface[][]
     */
    protected static $pluginClassNames = [];

    /**
     * @param string|HttpPluginInterface|CliPluginInterface|GenericPluginInterface $pluginClassName
     */
    public static function addPlugin(string $pluginClassName, string $timeSlot): void
    {
        self::$pluginClassNames[$timeSlot][] = $pluginClassName;
    }

    public static function startPlugins(string $timeSlot, Container $container): void
    {
        if (!array_key_exists($timeSlot, self::$pluginClassNames)) {
            return;
        }

        foreach (self::$pluginClassNames[$timeSlot] as $pluginClassName) {
            $plugin = $container->get($pluginClassName);

            $validPluginInterface = false;

            foreach (self::$acceptedPluginInterfaces as $acceptedPluginInterface) {
                if ($plugin instanceof $acceptedPluginInterface) {
                    $validPluginInterface = true;
                    break;
                }
            }

            if (!$validPluginInterface) {
                throw new Exception(sprintf(
                    "Plugin '%s' does not implement a valid plugin interface (%s)",
                    $pluginClassName,
                    implode(', ', self::$acceptedPluginInterfaces)
                ));
            }

            $dependencies = [$container->get(Config::class)];

            if ($plugin instanceof HttpPluginInterface && Context::isHttp()) {
                $dependencies[] = $container->get(Router::class);
            } elseif ($plugin instanceof CliPluginInterface && Context::isCli()) {
                $dependencies[] = $container->get(ConsoleApplication::class);
            }

            $plugin->configure(...$dependencies);
        }
    }
}
