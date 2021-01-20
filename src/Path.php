<?php declare(strict_types=1);

namespace Parable\Framework;

class Path
{
    protected string $root;

    public function __construct(string $root = BASEDIR)
    {
        $this->root = rtrim($root, '/');
    }

    public function getRoot(): string
    {
        return $this->root;
    }

    public function getPath(string $path): string
    {
        return sprintf(
            '%s/%s',
            $this->root,
            trim($path, '/')
        );
    }
}
