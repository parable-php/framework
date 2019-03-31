<?php declare(strict_types=1);

namespace Parable\Framework\Plugins;

use Parable\Framework\Config;

interface GenericPluginInterface
{
    public function configure(Config $config): void;
}
