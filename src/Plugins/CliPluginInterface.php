<?php declare(strict_types=1);

namespace Parable\Framework\Plugins;

use Parable\Framework\Config;
use Parable\Framework\ConsoleApplication;

interface CliPluginInterface
{
    public function configure(Config $config, ConsoleApplication $application): void;
}
