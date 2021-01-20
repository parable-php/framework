<?php

declare(ticks=1, strict_types=1);

namespace Parable\Framework\Commands;

use Parable\Console\Command;
use Parable\Console\Output;

class ServerCommand extends Command
{
    /** @var string */
    protected $name = 'server';

    /** @var string */
    protected $description = 'Run Parable with PHP\'s built-in server.';

    protected static ?string $public;
    protected static ?int $port;
    protected static Output $outputStatic;

    public function run(): void
    {
        self::$outputStatic = $this->output;

        pcntl_signal(SIGTERM, [self::class, 'signalHandler']);
        pcntl_signal(SIGHUP, [self::class, 'signalHandler']);
        pcntl_signal(SIGINT, [self::class, 'signalHandler']);

        $this->output->write('Enter your public folder [public]: ');
        self::$public = $this->input->get();

        $this->output->write('Enter the port [random]: ');
        self::$port = (int)$this->input->get();

        if (self::$public === 0) {
            self::$public = 'public';
        }

        /* Setting the port to 0 will take a randomly available port */
        if (empty(self::$port)) {
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
