<?php declare(strict_types=1);

namespace Parable\Framework\Tests;

use DateTime;
use Parable\Event\EventManager;
use Parable\Framework\Application;
use Parable\Framework\Config;
use Parable\Framework\Context;
use Parable\Framework\EventTriggers;
use Parable\Framework\Exception;
use Parable\Framework\Path;
use Parable\Http\HeaderSender;
use Parable\Http\Response;
use Parable\Orm\Database;
use Parable\Routing\Route;
use Parable\Routing\Router;

class ApplicationTest extends AbstractTestCase
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var array
     */
    protected $triggeredEvents = [];

    public function setUp()
    {
        parent::setUp();

        HeaderSender::setTestMode(true);

        try {
            Context::setIsCliForTest(false);
        } catch (Exception $e) {
            throw $e;
            // We don't care if it worked at this point, tests will fail if it's important
        }

        $this->config = $this->container->get(Config::class);
        $this->config->set('parable.session.enabled', false);

        $eventManager = $this->container->get(EventManager::class);
        $eventManager->listen('*', function ($event, $payload) {
            $this->triggeredEvents[$event] = $payload;
        });

        $_SERVER['HTTP_HOST'] = 'test.dev';
    }

    public function tearDown()
    {
        parent::tearDown();

        unset($_SERVER['HTTP_HOST']);
    }

    public function testApplicationThrowsExceptionWhenInCliMode(): void
    {
        Context::setIsCliForTest(true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Application cannot be used in CLI context.");

        $this->container->build(Application::class);
    }

    public function testApplicationCreationAndBoot(): void
    {
        $application = $this->container->build(Application::class);

        self::assertFalse($application->hasBooted());

        $application->boot();

        // All triggers come in sets, unless an exception is thrown.
        self::assertSame(
            [
                EventTriggers::APPLICATION_BOOT_BEFORE,

                EventTriggers::APPLICATION_PLUGINS_START_BEFORE_BOOT_BEFORE,
                EventTriggers::APPLICATION_PLUGINS_START_BEFORE_BOOT_AFTER,

                EventTriggers::APPLICATION_SET_DEFAULT_TIMEZONE_BEFORE,
                EventTriggers::APPLICATION_SET_DEFAULT_TIMEZONE_AFTER,

                EventTriggers::APPLICATION_PLUGINS_START_AFTER_BOOT_BEFORE,
                EventTriggers::APPLICATION_PLUGINS_START_AFTER_BOOT_AFTER,

                EventTriggers::APPLICATION_BOOT_AFTER,
            ],
            array_keys($this->triggeredEvents)
        );

        self::assertTrue($application->hasBooted());
    }

    public function testApplicationCannotBeBootedTwice(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("App has already booted.");

        $application = $this->container->build(Application::class);

        self::assertFalse($application->hasBooted());

        $application->boot();

        self::assertTrue($application->hasBooted());

        // This one will throw an exception
        $application->boot();
    }

    public function testApplicationDependenciesCanBeChangedBeforeBoot(): void
    {
        $application = new class (...$this->container->getDependenciesFor(Application::class)) extends Application {
            public function path(): ?Path
            {
                return $this->path;
            }
        };

        self::assertInstanceOf(Path::class, $this->container->get(Path::class));
        self::assertContains('parable/tests', $this->container->get(Path::class)->getRoot());

        // Application's 'dependencies' are not yet set.
        self::assertNull($application->path());

        $path = new Path('stuff/here');
        $this->container->store($path);

        $application->boot();

        self::assertInstanceOf(Path::class, $application->path());
        self::assertNotContains('parable/tests', $application->path()->getRoot());
        self::assertSame('stuff/here/what', $application->path()->getPath('what'));
    }

    public function testApplicationRunWithoutAnythingSetUpWillHandleAs404(): void
    {
        $application = $this->container->build(Application::class);

        $application->run();

        self::assertSame('404 - page not found', $this->getActualOutputAndClean());
        self::assertContains(
            EventTriggers::APPLICATION_ROUTE_MATCH_NOT_FOUND,
            array_keys($this->triggeredEvents)
        );

        $response = $this->container->get(Response::class);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('Not Found', $response->getStatusCodeText());
    }

    public function testApplicationDispatchesFoundRoute(): void
    {
        $router = $this->container->get(Router::class);

        $router->add(['GET'], 'test-index', '/', function () {
            echo 'test route found!';
        });

        $application = $this->container->build(Application::class);
        $application->run();

        self::assertSame('test route found!', $this->getActualOutputAndClean());
        self::assertContains(
            EventTriggers::APPLICATION_ROUTE_MATCH_FOUND,
            array_keys($this->triggeredEvents)
        );

        /** @var Route|null $route */
        $route = $this->triggeredEvents[EventTriggers::APPLICATION_ROUTE_MATCH_FOUND] ?? null;

        self::assertInstanceOf(Route::class, $route);
        self::assertSame('test-index', $route->getName());

        $response = $this->container->get(Response::class);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getStatusCodeText());
    }

    public function testErrorReportingIsPickedUpFromConfig(): void
    {
        // By default it's off
        $this->container->build(Application::class)->run();

        self::assertNotSame('1', ini_get('display_errors'));

        // Now we turn it on
        $this->config->set('parable.debug.enabled', true);

        $this->container->build(Application::class)->run();

        self::assertSame('1', ini_get('display_errors'));

        // And we turn it off again
        $this->config->set('parable.debug.enabled', false);

        $this->container->build(Application::class)->run();

        self::assertNotSame('1', ini_get('display_errors'));

        $this->getActualOutputAndClean();
    }

    public function testTimeZoneIsPickedUpFromConfig(): void
    {
        $this->container->build(Application::class)->run();

        self::assertSame('UTC', date_default_timezone_get());
        self::assertSame('UTC', (new DateTime())->getTimezone()->getName());
        self::assertSame(
            'UTC',
            $this->triggeredEvents[EventTriggers::APPLICATION_SET_DEFAULT_TIMEZONE_AFTER]
        );

        $this->config->set('parable.default-timezone', 'Europe/Amsterdam');

        $this->container->build(Application::class)->run();

        self::assertSame('Europe/Amsterdam', date_default_timezone_get());
        self::assertSame('Europe/Amsterdam', (new DateTime())->getTimezone()->getName());
        self::assertSame(
            'Europe/Amsterdam',
            $this->triggeredEvents[EventTriggers::APPLICATION_SET_DEFAULT_TIMEZONE_AFTER]
        );

        $this->getActualOutputAndClean();
    }

    public function testDatabaseIsPickedUpFromConfig(): void
    {
        $this->container->build(Application::class)->run();

        self::assertNotContains(
            EventTriggers::APPLICATION_INIT_DATABASE_BEFORE,
            array_keys($this->triggeredEvents)
        );

        self::assertNotContains(
            EventTriggers::APPLICATION_INIT_DATABASE_AFTER,
            array_keys($this->triggeredEvents)
        );

        // Now we set the database values
        $this->config->set('parable.database', [
            'type' => Database::TYPE_SQLITE,
            'database' => ':memory:',
        ]);

        $this->container->build(Application::class)->run();

        self::assertContains(
            EventTriggers::APPLICATION_INIT_DATABASE_BEFORE,
            array_keys($this->triggeredEvents)
        );

        self::assertInstanceOf(
            Database::class,
            $this->triggeredEvents[EventTriggers::APPLICATION_INIT_DATABASE_AFTER]
        );

        $this->getActualOutputAndClean();
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionIsPickedUpFromConfig(): void
    {
        $this->container->build(Application::class)->run();

        self::assertNotContains(
            EventTriggers::APPLICATION_SESSION_START_BEFORE,
            array_keys($this->triggeredEvents)
        );

        self::assertNotContains(
            EventTriggers::APPLICATION_SESSION_START_AFTER,
            array_keys($this->triggeredEvents)
        );

        $this->config->set('parable.session.enabled', true);

        $this->container->build(Application::class)->run();

        self::assertContains(
            EventTriggers::APPLICATION_SESSION_START_BEFORE,
            array_keys($this->triggeredEvents)
        );

        self::assertSame('PHPSESSID', $this->triggeredEvents[EventTriggers::APPLICATION_SESSION_START_AFTER]);

        $this->config->set('parable.session.name', 'session name');

        $this->container->build(Application::class)->run();

        self::assertContains(
            EventTriggers::APPLICATION_SESSION_START_BEFORE,
            array_keys($this->triggeredEvents)
        );

        self::assertSame('session name', $this->triggeredEvents[EventTriggers::APPLICATION_SESSION_START_AFTER]);

        $this->getActualOutputAndClean();
    }
}
