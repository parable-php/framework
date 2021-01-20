<?php declare(strict_types=1);

namespace Parable\Framework\Tests;

use Parable\Console\Application;
use Parable\Console\Commands\HelpCommand;
use Parable\Framework\Commands\InstallCommand;
use Parable\Framework\Commands\ServerCommand;
use Parable\Framework\ConsoleApplication;
use Parable\Framework\Tests\Classes\ConsoleApplicationWrapper;

class ConsoleApplicationTest extends AbstractTestCase
{
    public function testConsoleApplicationSetsHelpInstallAndServerCommands(): void
    {
        $application = $this->container->get(ConsoleApplicationWrapper::class);
        $this->container->store($application, Application::class);

        $consoleApplication = $this->container->get(ConsoleApplication::class);

        self::assertCount(0, $application->getCommands());

        $consoleApplication->run();

        $commands = $application->getCommands();

        self::assertCount(3, $commands);
        self::assertArrayHasKey('help', $commands);
        self::assertArrayHasKey('install', $commands);
        self::assertArrayHasKey('server', $commands);

        self::assertInstanceOf(HelpCommand::class, $commands['help']);
        self::assertInstanceOf(InstallCommand::class, $commands['install']);
        self::assertInstanceOf(ServerCommand::class, $commands['server']);
    }
}
