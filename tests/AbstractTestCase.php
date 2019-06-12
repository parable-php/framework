<?php declare(strict_types=1);

namespace Parable\Framework\Tests;

use Parable\Di\Container;
use Parable\Framework\Context;
use Parable\Framework\Path;

class AbstractTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var int
     */
    protected $startingOutputBufferLevel;

    public function setUp()
    {
        parent::setUp();

        $this->container = new Container();

        if (!defined('RUNNING_IN_TEST')) {
            define('RUNNING_IN_TEST', true);
        }

        $this->container->store(new Path(__DIR__));
    }

    public function tearDown()
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
    public function getActualOutputAndClean()
    {
        $content = parent::getActualOutput();
        ob_clean();
        return $content;
    }
}
