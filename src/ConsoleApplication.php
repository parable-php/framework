<?php declare(strict_types=1);

namespace Parable\Framework;

use Parable\Console\Application;
use Parable\Console\Commands\HelpCommand;
use Parable\Di\Container;
use Parable\Framework\Commands\InstallCommand;

class ConsoleApplication
{
    /**
     * @var Application
     */
    protected $application;

    /**
     * @var Container
     */
    protected $container;

    public function __construct(
        Application $application,
        Container $container
    ) {
        $this->application = $application;
        $this->container = $container;
    }

    public function run(): void
    {
        $this->application->addCommandByNameAndClass('help', HelpCommand::class);
        $this->application->addCommandByNameAndClass('install', InstallCommand::class);

        $this->application->setDefaultCommandByName('help');

        $this->application->run();
    }
}
