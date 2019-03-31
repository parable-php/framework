<?php declare(strict_types=1);

namespace Parable\Framework\Plugins;

use Parable\Framework\Config;
use Parable\Routing\Router;

interface HttpPluginInterface
{
    public function configure(Config $config, Router $router): void;
}
