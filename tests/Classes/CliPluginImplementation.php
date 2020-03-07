<?php declare(strict_types=1);

namespace Parable\Framework\Tests\Classes;

use Parable\Framework\Config;
use Parable\Framework\ConsoleApplication;
use Parable\Framework\Plugins\PluginInterface;

class CliPluginImplementation implements PluginInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConsoleApplication
     */
    protected $application;

    public function __construct(
        Config $config,
        ConsoleApplication $application
    ) {
        $this->config = $config;
        $this->application = $application;
    }

    public function run(): void
    {
        $this->config->set('dependencies', [$this->config, $this->application]);
    }
}
