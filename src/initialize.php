<?php declare(strict_types=1);

use \Parable\Framework\FrameworkException;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('BASEDIR')) {
    $basedir = realpath(__DIR__ . DS . '..' . DS . '..' . DS . '..' . DS . '..' . DS . 'vendor' . DS . '..');

    if ($basedir === false) {
        $basedir = realpath(__DIR__ . DS . '..' . DS . 'vendor' . DS . '..');
    }

    if ($basedir === false) {
        throw new FrameworkException('Could not determine base path.');
    }

    define('BASEDIR', $basedir);
}
