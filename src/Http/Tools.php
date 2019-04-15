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
        return rtrim(
            rtrim(
                $this->getBaseUrl(),
                '/'
            ) . '/' . $path,
            '/'
        );
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

    public function buildUrlFromRoute(Route $route, array $parameters = []): string
    {
        $url = $route->getUrl();

        foreach ($parameters as $param => $value) {
            $url = str_replace('{' . $param . '}', $value, $url);
        }

        return $this->buildUrl($url);
    }
}
