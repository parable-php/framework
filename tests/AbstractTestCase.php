<?php declare(strict_types=1);

namespace Parable\Framework\Tests;

use Parable\Di\Container;
use Parable\Framework\Context;
use Parable\Framework\Path;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    protected Container $container;

    public function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();

        if (!defined('RUNNING_IN_TEST')) {
            define('RUNNING_IN_TEST', true);
        }

        $this->container->store(new Path(__DIR__));
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Context::clearIsCliForTest();
    }

    /**
     * Returns the actual output form the default PHPUnit output buffer,
     * and cleans 1(!) level, clearing the most recent buffer level.
     *
     * @return string
     */
    public function getActualOutputAndClean(): string
    {
        $content = $this->getActualOutput();
        ob_clean();
        return $content;
    }
}
