<?php declare(strict_types=1);

namespace Parable\Framework\Http;

use Parable\Framework\FrameworkException;
use Parable\GetSet\GetCollection;
use Parable\GetSet\ServerCollection;
use Parable\Http\HeaderSender;
use Parable\Http\Request;
use Parable\Routing\Route;
use Parable\Routing\Router;

class Tools
{
    public function __construct(
        protected GetCollection $get,
        protected ServerCollection $server,
        protected Request $request,
        protected Router $router
    ) {}

    public function getBaseUrl(): string
    {
        $baseUri = $this->request->getUri()
            ->withFragment(null)
            ->withQuery(null);

        return $this->replaceAndClean(
            $this->getCurrentRelativeUrl(),
            '/',
            $baseUri->getUriString()
        );
    }

    public function getCurrentRelativeUrl(): string
    {
        if ($this->isCliServer()) {
            $this->get->set('PARABLE_REDIRECT_URL', $this->server->get('PATH_INFO') ?? '');
        }

        if ($this->get->get('PARABLE_REDIRECT_URL') === null) {
            return '/';
        }

        return $this->clean((string)$this->get->get('PARABLE_REDIRECT_URL'));
    }

    public function getCurrentUrl(): string
    {
        return $this->request->getUri()->getUriString();
    }

    public function buildUrl(string $path): string
    {
        return sprintf(
            '%s/%s',
            rtrim($this->getBaseUrl(), '/'),
            trim($path, '/')
        );
    }

    public function redirect(string $target): void
    {
        HeaderSender::send(sprintf('location: %s', $target));

        $this->terminate(0);
    }

    public function redirectToSelf(): void
    {
        $this->redirect($this->getCurrentUrl());
    }

    public function redirectToRoute(Route $route, array $parameters = []): void
    {
        $this->redirect($this->buildUrlFromRoute($route, $parameters));
    }

    public function buildUrlFromRouteName(string $routeName, array $parameters = []): string
    {
        $route = $this->router->getRouteByName($routeName);

        if ($route === null) {
            throw new FrameworkException(sprintf("Could not find route named '%s'", $routeName));
        }

        return $this->buildUrlFromRoute($route, $parameters);
    }

    public function buildUrlFromRoute(Route $route, array $parameters = []): string
    {
        $url = $route->getUrl();

        foreach ($parameters as $param => $value) {
            $url = $this->replaceAndClean(
                sprintf('{%s}', $param),
                $value,
                $url
            );
        }

        return $this->buildUrl($url);
    }

    public function terminate(int $exitCode): void
    {
        exit($exitCode); // @codeCoverageIgnore
    }

    protected function isCliServer(): bool
    {
        return PHP_SAPI === 'cli-server';
    }

    protected function replaceAndClean(string $search, string $replace, string $string): string
    {
        return $this->clean(str_replace(
            $search,
            $replace,
            $string
        ));
    }

    protected function clean(string $string): string
    {
        return trim($string, '/');
    }
}
