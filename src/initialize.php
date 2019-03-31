<?php declare(strict_types=1);

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('BASEDIR')) {
    define('BASEDIR', realpath(__DIR__ . DS . '..' . DS . '..' . DS . '..' . DS . '..'));
}
