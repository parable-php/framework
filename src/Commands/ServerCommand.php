<?php

declare(ticks=1, strict_types=1);

namespace Parable\Framework\Commands;

use Parable\Console\Command;
use Parable\Console\Output;
use Parable\Console\Parameter;

class ServerCommand extends Command
{
    protected static ?string $public = null;
    protected static ?int $port = null;
    protected static ?Output $outputStatic = null;

    public function __construct()
    {
        $this->addOption('public', Parameter::OPTION_VALUE_REQUIRED);
        $this->addOption('port');

        $this->setName('server');
        $this->setDescription('Run Parable with PHP\'s built-in server.');
    }

    public function run(): void
    {
        self::$outputStatic = $this->output;

        pcntl_signal(SIGTERM, [self::class, 'signalHandler']);
        pcntl_signal(SIGHUP, [self::class, 'signalHandler']);
        pcntl_signal(SIGINT, [self::class, 'signalHandler']);

        if ((self::$public = $this->parameter->getOption('public')) === null) {
            $this->output->write('Enter your public folder [public]: ');
            self::$public = $this->input->get();
        }

        if ($this->parameter->getOption('port') !== null) {
            if ($this->parameter->getOption('port') === true) {
                self::$port = 0; // this will cause a random port
            } else {
                self::$port = (int)$this->parameter->getOption('port');
            }
        } else {
            $this->output->write('Enter the port [random]: ');
            self::$port = (int)$this->input->get();
        }

        if (empty(self::$public)) {
            self::$public = 'public';
        }

        /* Setting the port to 0 will take a randomly available port */
        if (self::$port === 0) {
            self::$port = random_int(51000, 53000);
        }

        $fullCommand = sprintf(
            'cd %s && php -S localhost:%d',
            self::$public,
            self::$port
        );

        $this->output->writeBlock(
            [
                sprintf(
                    "Starting server for 'public' folder %s on http://localhost:%s",
                    self::$public,
                    self::$port
                )
            ],
            ['success']
        );

        shell_exec($fullCommand);
    }

    protected static function signalHandler(): void
    {
        self::$outputStatic->newline();
        self::$outputStatic->writeBlock(
            [
                sprintf(
                    "Stopped server for 'public' folder %s on http://localhost:%s",
                    self::$public,
                    self::$port
                )
            ],
            ['success']
        );
    }
}
