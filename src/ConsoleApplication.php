<?php declare(strict_types=1);

namespace Parable\Framework;

use Parable\Console\Application;
use Parable\Console\Commands\HelpCommand;
use Parable\Di\Container;
use Parable\Framework\Commands\InstallCommand;
use Parable\Framework\Commands\ServerCommand;

class ConsoleApplication
{
    public function __construct(
        protected Application $application,
        protected Container $container
    ) {
    }

    public function run(): void
    {
        $this->application->addCommandByNameAndClass('help', HelpCommand::class);
        $this->application->addCommandByNameAndClass('install', InstallCommand::class);
        $this->application->addCommandByNameAndClass('server', ServerCommand::class);

        $this->application->setDefaultCommandByName('help');

        $this->application->run();
    }
}
