<?php declare(strict_types=1);

namespace Parable\Framework\Http;

use Parable\GetSet\GetCollection;
use Parable\Http\HeaderSender;
use Parable\Http\Request;
use Parable\Routing\Route;

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

    public function __construct(
        GetCollection $getCollection,
        Request $request
    ) {
        $this->getCollection = $getCollection;
        $this->request = $request;
    }

    public function getCurrentRelativeUrl(): string
    {
        $currentRelativeUrl = $this->getCollection->get('PARABLE_REDIRECT_URL');

        if ($currentRelativeUrl === null) {
            return '/';
        }

        return (string)$currentRelativeUrl;
    }

    public function getCurrentFullUrl(): string
    {
        return str_replace(':80', '', $this->request->getUri()->getUriString());
    }

    public function getBaseUrl(): string
    {
        return str_replace($this->getCurrentRelativeUrl(), '', $this->getCurrentFullUrl());
    }

    public function buildUrl(string $path): string
    {
        return $this->getBaseUrl() . '/' . $path;
    }

    public function redirect(string $target): void
    {
        HeaderSender::send('location: ' . $target);
    }

    public function redirectToRoute(Route $route, array $parameters = []): void
    {
        $this->redirect($this->buildUrlFromRoute($route, $parameters));
    }

    public function buildUrlFromRoute(Route $route, array $parameters = []): string
    {
        $url = $route->getUrl();

        foreach ($parameters as $param => $value) {
            $url = str_replace('{' . $param . '}', $value, $url);
        }

        return $url;
    }
}
