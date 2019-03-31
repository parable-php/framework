<?php declare(strict_types=1);

namespace Parable\Framework;

class Path
{
    public function getPath(string $path): string
    {
        return BASEDIR . '/' . trim($path, '/');
    }
}
