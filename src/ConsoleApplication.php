<?php declare(strict_types=1);

namespace Parable\Framework;

use Parable\Framework\Commands\Install;
use Parable\Console\App;
use Parable\Console\Command\Help;
use Parable\Di\Container;

class ConsoleApplication
{
    /**
     * @var App
     */
    protected $application;

    /**
     * @var Container
     */
    protected $container;

    public function __construct(
        App $application,
        Container $container
    ) {
        $this->application = $application;
        $this->container = $container;
    }

    public function run()
    {

        $this->application->addCommand($help = $this->container->get(Help::class));
        $this->application->addCommand($install = $this->container->get(Install::class));

        $this->application->setDefaultCommand($help);

        $this->application->run();
    }
}
