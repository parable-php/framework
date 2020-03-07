<?php declare(strict_types=1);

namespace Parable\Framework\Tests\Classes;

use Parable\Framework\Config;
use Parable\Framework\Plugins\PluginInterface;

class PluginImplementation implements PluginInterface
{
    /**
     * @var Config
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function run(): void
    {
        $this->config->set('dependencies', [$this->config]);
    }
}
