<?php declare(strict_types=1);

namespace Parable\Framework\Traits;

use Parable\Di\Container;
use Parable\Event\Events;
use Parable\Framework\Application;
use Parable\Framework\Config;
use Parable\Framework\DatabaseFactory;
use Parable\Framework\EventTriggers;
use Parable\Framework\Plugins\PluginManager;

trait BootableTrait
{
    protected function startPluginsBeforeBoot(Events $events, Container $container): void
    {
        $events->trigger(EventTriggers::APPLICATION_PLUGINS_START_BEFORE_BOOT_BEFORE);

        PluginManager::startPlugins(Application::PLUGIN_BEFORE_BOOT, $container);

        $events->trigger(EventTriggers::APPLICATION_PLUGINS_START_BEFORE_BOOT_AFTER);
    }

    protected function startPluginsAfterBoot(Events $events, Container $container): void
    {
        $events->trigger(EventTriggers::APPLICATION_PLUGINS_START_AFTER_BOOT_BEFORE);

        PluginManager::startPlugins(Application::PLUGIN_AFTER_BOOT, $container);

        $events->trigger(EventTriggers::APPLICATION_PLUGINS_START_AFTER_BOOT_AFTER);
    }

    protected function enableErrorReporting(): void
    {
        ini_set('display_errors', '1');
        error_reporting($this->config->get('parable.debug.levels') ?? E_ALL);
    }

    protected function disableErrorReporting(): void
    {
        if (ini_get('display_errors') !== '1') {
            return;
        }

        ini_set('display_errors', '0');
        error_reporting(E_ALL | ~E_DEPRECATED);
    }

    protected function setDefaultTimeZone(string $timezone): void
    {
        $this->events->trigger(EventTriggers::APPLICATION_SET_DEFAULT_TIMEZONE_BEFORE, $timezone);

        date_default_timezone_set($timezone);

        $currentlySetTimezone = date_default_timezone_get();

        $this->events->trigger(
            EventTriggers::APPLICATION_SET_DEFAULT_TIMEZONE_AFTER,
            $currentlySetTimezone
        );
    }

    protected function setupDatabaseFromConfig(Config $config): void
    {
        $this->events->trigger(EventTriggers::APPLICATION_INIT_DATABASE_BEFORE);

        $databaseFactory = new DatabaseFactory();
        $database = $databaseFactory->createFromConfig($config);

        $this->container->store($database);

        $database->connect();

        $this->events->trigger(EventTriggers::APPLICATION_INIT_DATABASE_AFTER, $database);
    }
}
