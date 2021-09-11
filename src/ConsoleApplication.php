<?php declare(strict_types=1);

namespace Parable\Framework;

use Parable\Console\Application;
use Parable\Console\Commands\HelpCommand;
use Parable\Di\Container;
use Parable\Event\Events;
use Parable\Framework\Commands\InstallCommand;
use Parable\Framework\Commands\ServerCommand;
use Parable\Framework\Traits\BootableTrait;

class ConsoleApplication
{
    use BootableTrait;

    protected bool $hasBooted = false;

    public function __construct(
        protected Application $application,
        protected Config $config,
        protected Container $container,
        protected Events $events
    ) {}

    public function run(): void
    {
        $this->boot();

        $this->application->addCommandByNameAndClass('help', HelpCommand::class);
        $this->application->addCommandByNameAndClass('install', InstallCommand::class);
        $this->application->addCommandByNameAndClass('server', ServerCommand::class);

        $this->application->setDefaultCommandByName('help');

        $this->application->run();
    }

    public function boot(): void
    {
        $this->events->trigger(EventTriggers::APPLICATION_BOOT_BEFORE, $this);

        if ($this->hasBooted()) {
            throw new FrameworkException('ConsoleApplication has already booted.');
        }

        $this->startPluginsBeforeBoot($this->events, $this->container);

        if ($this->config->get('parable.debug.enabled') === true) {
            $this->enableErrorReporting();
        } else {
            $this->disableErrorReporting();
        }

        $timezone = $this->config->get('parable.default-timezone');
        if (is_string($timezone)) {
            $this->setDefaultTimezone($timezone);
        } else {
            $this->setDefaultTimeZone('UTC');
        }

        if ($this->config->has('parable.database.type')) {
            $this->setupDatabaseFromConfig($this->config);
        }


        $this->startPluginsAfterBoot($this->events, $this->container);

        $this->hasBooted = true;

        $this->events->trigger(EventTriggers::APPLICATION_BOOT_AFTER, $this);
    }

    public function hasBooted(): bool
    {
        return $this->hasBooted;
    }
}
