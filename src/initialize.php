<?php declare(strict_types=1);

use \Parable\Framework\Exception;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('BASEDIR')) {
    $basedir = realpath(__DIR__ . DS . '..' . DS . '..' . DS . '..' . DS . '..' . DS . 'vendor' . DS . '..');

    if ($basedir === false) {
        $basedir = realpath(__DIR__ . DS . '..' . DS . 'vendor' . DS . '..');
    }

    if ($basedir === false) {
        throw new Exception('Could not determine base path.');
    }

    define('BASEDIR', $basedir);
}

if (defined('PHPUNIT_COMPOSER_INSTALL')) {
    define('IN_TEST', true);
} else {
    define('IN_TEST', false);
}
