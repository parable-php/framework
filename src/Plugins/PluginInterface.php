<?php declare(strict_types=1);

namespace Parable\Framework\Plugins;

interface PluginInterface
{
    public function run(): void;
}
