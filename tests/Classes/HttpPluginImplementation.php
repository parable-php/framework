<?php declare(strict_types=1);

namespace Parable\Framework\Tests\Classes;

use Parable\Framework\Config;
use Parable\Framework\Plugins\HttpPluginInterface;
use Parable\Routing\Router;

class HttpPluginImplementation implements HttpPluginInterface
{
    public function configure(Config $config, Router $router): void
    {
        $config->set('dependencies', [$config, $router]);
    }
}
