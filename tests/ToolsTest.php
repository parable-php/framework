<?php declare(strict_types=1);

namespace Parable\Framework\Tests;

use Parable\Framework\Exception;
use Parable\Framework\Http\Tools;
use Parable\GetSet\GetCollection;
use Parable\Http\HeaderSender;
use Parable\Http\Request;
use Parable\Routing\Route;
use Parable\Routing\Router;

class ToolsTest extends AbstractTestCase
{
    protected $tools;

    public function setUp()
    {
        parent::setUp();

        $this->container->get(GetCollection::class)->set('PARABLE_REDIRECT_URL', 'page/home');
        $this->container->store(new Request('GET', 'https://test.dev/page/home'));

        $this->tools = new class (...$this->container->getDependenciesFor(Tools::class)) extends Tools
        {
            public function terminate(int $exitCode): void
            {
                // Do nothing here
            }
        };
    }

    public function testGetBaseCurrentAndCurrentRelativeUrl(): void
    {
        self::assertSame('https://test.dev', $this->tools->getBaseUrl());
        self::assertSame('https://test.dev/page/home', $this->tools->getCurrentUrl());
        self::assertSame('page/home', $this->tools->getCurrentRelativeUrl());
    }

    public function testBuildUrl(): void
    {
        self::assertSame('https://test.dev/yo/dog', $this->tools->buildUrl('yo/dog'));
    }

    public function testRedirect(): void
    {
        HeaderSender::setTestMode(true);

        $this->tools->redirect('https://github.com');

        self::assertContains('location: https://github.com', HeaderSender::list());
    }

    public function testRedirectToSelf(): void
    {
        HeaderSender::setTestMode(true);

        self::assertSame('https://test.dev/page/home', $this->tools->getCurrentUrl());

        $this->tools->redirectToSelf();

        self::assertContains('location: https://test.dev/page/home', HeaderSender::list());
    }

    public function testRedirectToRoute(): void
    {
        HeaderSender::setTestMode(true);

        $this->tools->redirectToRoute(new Route(
            ['GET'],
            'redirect-route',
            'blah/blah',
            function () {}
        ));

        self::assertContains('location: https://test.dev/blah/blah', HeaderSender::list());
    }

    public function testBuildUrlFromRoute(): void
    {
        self::assertSame(
            'https://test.dev/blah/blah',
            $this->tools->buildUrlFromRoute(new Route(
                ['GET'],
                'redirect-route',
                'blah/blah',
                function () {}
            ))
        );
    }

    public function testBuildUrlWithParametersFromRoute(): void
    {
        self::assertSame(
            'https://test.dev/parameters/hello/world',
            $this->tools->buildUrlFromRoute(new Route(
                ['GET'],
                'redirect-route',
                'parameters/{no1}/{no2}',
                function () {}
            ), [
                'no1' => 'hello',
                'no2' => 'world',
            ])
        );
    }

    public function testBuildFromRouteName(): void
    {
        $this->container->get(Router::class)->add(
            ['GET'],
            'redirect-route',
            'blah/blah',
            function () {}
        );

        self::assertSame(
            'https://test.dev/blah/blah',
            $this->tools->buildUrlFromRouteName('redirect-route')
        );
    }

    public function testBuildFromRouteNameWithParameters(): void
    {
        $this->container->get(Router::class)->add(
            ['GET'],
            'redirect-route',
            'parameters/{no1}/{no2}',
            function () {}
        );

        self::assertSame(
            'https://test.dev/parameters/hello/world',
            $this->tools->buildUrlFromRouteName(
                'redirect-route',
                [
                    'no1' => 'hello',
                    'no2' => 'world',
                ]
            )
        );
    }

    public function testBuildFromRouteNameThrowsExceptionForUnknownRoute(): void
    {
        $this->expectExceptionMessage("Could not find route named 'nope'");
        $this->expectException(Exception::class);

        $this->tools->buildUrlFromRouteName('nope');
    }
}
