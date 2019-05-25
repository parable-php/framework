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
    protected $getCollection;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Router
     */
    protected $router;

    public function __construct(
        GetCollection $getCollection,
        Request $request,
        Router $router
    ) {
        $this->getCollection = $getCollection;
        $this->request = $request;
        $this->router = $router;
    }

    public function getBaseUrl(): string
    {
        return trim(
            str_replace($this->getCurrentRelativeUrl(), '/', $this->request->getUri()->getUriString()),
            '/'
        );
    }

    public function getCurrentRelativeUrl(): string
    {
        $currentRelativeUrl = $this->getCollection->get('PARABLE_REDIRECT_URL');

        return $currentRelativeUrl ?? '/';
    }

    public function getCurrentUrl(): string
    {
        return $this->request->getUri()->getUriString();
    }

    public function buildUrl(string $path): string
    {
        return rtrim($this->getBaseUrl(), '/') . '/' . trim($path, '/');
    }

    public function redirect(string $target): void
    {
        HeaderSender::send('location: ' . $target);

        exit(0);
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
            throw new Exception(sprintf('Could not find route named %s', $routeName));
        }

        return $this->buildUrlFromRoute($route, $parameters);
    }

    public function buildUrlFromRoute(Route $route, array $parameters = []): string
    {
        $url = $route->getUrl();

        foreach ($parameters as $param => $value) {
            $url = str_replace('{' . $param . '}', $value, $url);
        }

        return $this->buildUrl($url);
    }
}
