<?php declare(strict_types=1);

namespace Parable\Framework\Tests;

use Parable\Framework\FrameworkException;
use Parable\Framework\Http\Tools;
use Parable\GetSet\GetCollection;
use Parable\GetSet\ServerCollection;
use Parable\Http\HeaderSender;
use Parable\Http\Request;
use Parable\Routing\Route;
use Parable\Routing\Router;

class ToolsTest extends AbstractTestCase
{
    /** @var Tools */
    protected $tools;

    public function testProjectInSubfolderHasBaseUrlRecognizedProperly(): void
    {
        $this->setUpRequestAndToolsForUrl('https://test.dev/project', 'page/home');

        self::assertSame('https://test.dev/project', $this->tools->getBaseUrl());
        self::assertSame('https://test.dev/project/page/home', $this->tools->getCurrentUrl());
        self::assertSame('page/home', $this->tools->getCurrentRelativeUrl());
    }

    public function testGetBaseCurrentAndCurrentRelativeUrl(): void
    {
        $this->setUpRequestAndToolsForUrl('https://test.dev', 'page/home');

        self::assertSame('https://test.dev', $this->tools->getBaseUrl());
        self::assertSame('https://test.dev/page/home', $this->tools->getCurrentUrl());
        self::assertSame('page/home', $this->tools->getCurrentRelativeUrl());
    }

    public function testCurrentRelativeUrlWorksWithCliServer(): void
    {
        $this->setUpRequestAndToolsForUrl('https://test.dev', 'page/home', true);

        // If we're using the cli server, the getCollection won't have the key
        // So we attempt to take it from PATH_INFO instead
        self::assertSame('page/home', $this->tools->getCurrentRelativeUrl());
    }

    public function testBuildUrl(): void
    {
        $this->setUpRequestAndToolsForUrl('https://test.dev', 'page/home');

        self::assertSame('https://test.dev/yo/dog', $this->tools->buildUrl('yo/dog'));
    }

    public function testRedirect(): void
    {
        $this->setUpRequestAndToolsForUrl('https://test.dev', 'page/home');

        HeaderSender::setTestMode(true);

        $this->tools->redirect('https://github.com');

        self::assertContains('location: https://github.com', HeaderSender::list());
    }

    public function testRedirectToSelf(): void
    {
        $this->setUpRequestAndToolsForUrl('https://test.dev', 'page/home');

        HeaderSender::setTestMode(true);

        self::assertSame('https://test.dev/page/home', $this->tools->getCurrentUrl());

        $this->tools->redirectToSelf();

        self::assertContains('location: https://test.dev/page/home', HeaderSender::list());
    }

    public function testRedirectToRoute(): void
    {
        $this->setUpRequestAndToolsForUrl('https://test.dev', 'page/home');

        HeaderSender::setTestMode(true);

        $this->tools->redirectToRoute(new Route(
            ['GET'],
            'redirect-route',
            'blah/blah',
            fn() => null
        ));

        self::assertContains('location: https://test.dev/blah/blah', HeaderSender::list());
    }

    public function testBuildUrlFromRoute(): void
    {
        $this->setUpRequestAndToolsForUrl('https://test.dev', 'page/home');

        self::assertSame(
            'https://test.dev/blah/blah',
            $this->tools->buildUrlFromRoute(new Route(
                ['GET'],
                'redirect-route',
                'blah/blah',
                fn() => null
            ))
        );
    }

    public function testBuildUrlWithParametersFromRoute(): void
    {
        $this->setUpRequestAndToolsForUrl('https://test.dev', 'page/home');

        self::assertSame(
            'https://test.dev/parameters/hello/world',
            $this->tools->buildUrlFromRoute(new Route(
                ['GET'],
                'redirect-route',
                'parameters/{no1}/{no2}',
                fn() => null
            ), [
                'no1' => 'hello',
                'no2' => 'world',
            ])
        );
    }

    public function testBuildFromRouteName(): void
    {
        $this->setUpRequestAndToolsForUrl('https://test.dev', 'page/home');

        $this->container->get(Router::class)->add(
            ['GET'],
            'redirect-route',
            'blah/blah',
            fn() => null
        );

        self::assertSame(
            'https://test.dev/blah/blah',
            $this->tools->buildUrlFromRouteName('redirect-route')
        );
    }

    public function testBuildFromRouteNameWithParameters(): void
    {
        $this->setUpRequestAndToolsForUrl('https://test.dev', 'page/home');

        $this->container->get(Router::class)->add(
            ['GET'],
            'redirect-route',
            'parameters/{no1}/{no2}',
            fn() => null
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
        $this->setUpRequestAndToolsForUrl('https://test.dev', 'page/home');

        $this->expectExceptionMessage("Could not find route named 'nope'");
        $this->expectException(FrameworkException::class);

        $this->tools->buildUrlFromRouteName('nope');
    }

    public function testBuildUrlDoesNotCareAboutFragmentsOrQuery(): void
    {
        $this->setUpRequestAndToolsForUrl('https://test.dev', 'page/home/?query=1#fragment');

        self::assertSame('https://test.dev', $this->tools->getBaseUrl());

        self::assertSame(
            'https://test.dev/test/path#other-fragment=1',
            $this->tools->buildUrl('test/path#other-fragment=1')
        );
    }

    protected function setUpRequestAndToolsForUrl(
        string $baseUrl,
        string $relativePath,
        bool $isCliServer = false
    ): void {
        $request = new Request('GET', $baseUrl . '/' . $relativePath);

        $this->container->store($request);

        $redirectUrl = trim(str_replace(
            $baseUrl,
            '',
            $request->getUri()
                ->withFragment(null)
                ->withQuery(null)
                ->getUriString()
        ), '/');

        // PARABLE_REDIRECT_URL is always without fragments or query strings
        $this->container->get(GetCollection::class)->set(
            'PARABLE_REDIRECT_URL',
            $redirectUrl
        );

        $dependencies = $this->container->getDependenciesFor(Tools::class);
        $dependencies[] = $isCliServer;
        $dependencies[] = $relativePath;

        $this->tools = new class (...$dependencies) extends Tools {
            public function __construct(
                protected GetCollection $get,
                protected ServerCollection $server,
                protected Request $request,
                protected Router $router,
                protected bool $isCliServerCustom,
                string $relativePath
            ) {
                parent::__construct($get, $server, $request, $router);

                if ($isCliServerCustom) {
                    $server->set('PATH_INFO', $relativePath);
                }
            }

            public function terminate(int $exitCode): void
            {
                // Do nothing here
            }
            protected function isCliServer(): bool
            {
                return $this->isCliServerCustom;
            }
        };
    }
}
