<?php declare(strict_types=1);

namespace Parable\Framework\Http;

use Parable\Framework\Exception;
use Parable\GetSet\GetCollection;
use Parable\Http\HeaderSender;
use Parable\Http\Request;
use Parable\Routing\Route;
use Parable\Routing\Router;

class Tools
{
    /**
     * @var GetCollection
     */
    protected $get;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Router
     */
    protected $router;

    public function __construct(
        GetCollection $get,
        Request $request,
        Router $router
    ) {
        $this->get = $get;
        $this->request = $request;
        $this->router = $router;
    }

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
        return $this->get->get('PARABLE_REDIRECT_URL') !== null
            ? $this->clean((string)$this->get->get('PARABLE_REDIRECT_URL'))
            : '/';
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
            throw new Exception(sprintf("Could not find route named '%s'", $routeName));
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
