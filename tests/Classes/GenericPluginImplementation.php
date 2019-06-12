<?php declare(strict_types=1);

namespace Parable\Framework\Tests\Classes;

use Parable\Framework\Config;
use Parable\Framework\Plugins\GenericPluginInterface;

class GenericPluginImplementation implements GenericPluginInterface
{
    public function configure(Config $config): void
    {
        $config->set('dependencies', [$config]);
    }
}
