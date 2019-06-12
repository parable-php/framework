<?php declare(strict_types=1);

namespace Parable\Framework\Tests\Classes;

use Parable\Framework\Config;
use Parable\Framework\ConsoleApplication;
use Parable\Framework\Plugins\CliPluginInterface;

class CliPluginImplementation implements CliPluginInterface
{
    public function configure(Config $config, ConsoleApplication $application): void
    {
        $config->set('dependencies', [$config, $application]);
    }
}
