<?php declare(strict_types=1);

namespace Parable\Framework\Tests;

use Parable\Framework\Exception;
use Parable\Framework\Http\RouteDispatcher;
use Parable\Framework\Tests\Classes\ControllerAction;
use Parable\Http\Response;
use Parable\Routing\Route;

class RouteDispatcherTest extends AbstractTestCase
{
    /**
     * @var RouteDispatcher
     */
    protected $routeDispatcher;

    /**
     * @var Response
     */
    protected $response;

    public function setUp()
    {
        parent::setUp();

        $this->routeDispatcher = $this->container->build(RouteDispatcher::class);
        $this->response = $this->container->get(Response::class);
    }

    public function testDispatchClosureRouteWithoutParameters(): void
    {
        $route = new Route(['GET'], 'test-index', '/', function () {
            echo 'test route found!';
        });

        $this->routeDispatcher->dispatch($route);

        self::assertSame('test route found!', $this->response->getBody());
    }

    public function testDispatchControllerActionRouteWithoutParameters(): void
    {
        $route = new Route(['GET'], 'test-index', '/', [ControllerAction::class, 'Action']);

        $this->routeDispatcher->dispatch($route);

        self::assertSame('controller action route found!', $this->response->getBody());
    }

    public function testDispatchClosureRouteWithParameters(): void
    {
        $route = new Route(['GET'], 'test-parameters', '/{id1}/{id2}', function ($id1, $id2) {
            echo 'test parametered route found! (' . $id1 . ', ' . $id2 . ')';
        });

        $route->setParameterValues(new Route\ParameterValues([
            'id1' => 'yep',
            'id2' => 'definitely',
        ]));

        $this->routeDispatcher->dispatch($route);

        self::assertSame('test parametered route found! (yep, definitely)', $this->response->getBody());
    }

    public function testDispatchRouteWithTemplatePathInMetadata(): void
    {
        $route = new Route(['GET'], 'test-index', '/', function () {
            echo 'test route found!';
        }, [
            'template' => 'Classes/template.phtml',
        ]);

        $this->routeDispatcher->dispatch($route);

        self::assertSame(
            "test route found!\nThis is a render of " . __DIR__ . '/Classes/template.phtml',
            $this->response->getBody()
        );
    }

    public function testWhenExceptionIsThrownAllOutputBuffersAreClosed(): void
    {
        self::assertFalse($this->routeDispatcher->hasActiveOutputBuffer());

        $route = new Route(['GET'], 'test-index', '/', function () {
            throw new Exception('Nope.');
        });

        $e = null;

        try {
            $this->routeDispatcher->dispatch($route);
        } catch (Exception $e) {
            // 's fine
        }

        self::assertInstanceOf(Exception::class, $e);

        self::assertFalse($this->routeDispatcher->hasActiveOutputBuffer());
    }
}
