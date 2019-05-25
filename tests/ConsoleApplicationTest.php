<?php declare(strict_types=1);

namespace Parable\Framework\Tests;

use Parable\Console\Application;
use Parable\Console\Command\Help;
use Parable\Console\Commands\HelpCommand;
use Parable\Di\Container;
use Parable\Framework\Commands\InstallCommand;
use Parable\Framework\ConsoleApplication;
use Parable\Framework\Tests\Classes\ConsoleApplicationWrapper;

class ConsoleApplicationTest extends \PHPUnit\Framework\TestCase
{
    public function testConsoleApplicationSetsHelpAndInstallCommands(): void
    {
        $di = new Container();

        $application = $di->get(ConsoleApplicationWrapper::class);
        $di->store($application, Application::class);

        $consoleApplication = $di->get(ConsoleApplication::class);

        self::assertCount(0, $application->getCommands());

        $consoleApplication->run();

        $commands = $application->getCommands();

        self::assertCount(2, $commands);
        self::assertArrayHasKey('help', $commands);
        self::assertArrayHasKey('install', $commands);

        self::assertInstanceOf(HelpCommand::class, $commands['help']);
        self::assertInstanceOf(InstallCommand::class, $commands['install']);
    }
}
