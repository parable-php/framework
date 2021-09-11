<?php declare(strict_types=1);

namespace Parable\Framework;

use Parable\Di\Container;
use Parable\Event\Events;
use Parable\Framework\Http\RouteDispatcher;
use Parable\Framework\Http\Tools;
use Parable\Framework\Plugins\PluginManager;
use Parable\Framework\Traits\BootableTrait;
use Parable\GetSet\GetCollection;
use Parable\Http\Request;
use Parable\Http\Response;
use Parable\Http\ResponseDispatcher;
use Parable\Routing\Route;
use Parable\Routing\Router;

class Application
{
    use BootableTrait;

    public const VERSION = '2.0.3';

    public const PLUGIN_BEFORE_BOOT = 'plugin_before_boot';
    public const PLUGIN_AFTER_BOOT = 'plugin_after_boot';

    /* Replaceable instantiation */
    protected ?ResponseDispatcher $responseDispatcher = null;
    protected ?RouteDispatcher $routeDispatcher = null;

    protected bool $hasBooted = false;

    public function __construct(
        protected Container $container,
        protected Config $config,
        protected Events $events,
        protected GetCollection $get,
        protected Path $path,
        protected Request $request,
        protected Response $response,
        protected Router $router,
        protected Tools $tools,
    ) {
        if (Context::isCli()) {
            throw new FrameworkException('Application cannot be used in CLI context.');
        }
    }

    public function run(): void
    {
        $this->events->trigger(EventTriggers::APPLICATION_RUN_BEFORE, $this);

        if (!$this->hasBooted) {
            $this->boot();
        }

        $route = $this->matchRoute();

        if ($route === null) {
            $this->response->setStatusCode(404);
            $this->response->setBody('404 - page not found');
        } else {
            $this->routeDispatcher->dispatch($route);
        }

        $this->dispatchResponse();

        $this->events->trigger(EventTriggers::APPLICATION_RUN_AFTER, $this);
    }

    public function boot(): void
    {
        $this->events->trigger(EventTriggers::APPLICATION_BOOT_BEFORE, $this);

        if ($this->hasBooted()) {
            throw new FrameworkException('Application has already booted.');
        }

        $this->startPluginsBeforeBoot($this->events, $this->container);

        if ($this->config->get('parable.debug.enabled') === true) {
            $this->enableErrorReporting();
        } else {
            $this->disableErrorReporting();
        }

        $timezone = $this->config->get('parable.default-timezone');
        if (is_string($timezone)) {
            $this->setDefaultTimezone($timezone);
        } else {
            $this->setDefaultTimeZone('UTC');
        }

        if ($this->config->has('parable.database.type')) {
            $this->setupDatabaseFromConfig($this->config);
        }

        if ($this->config->get('parable.session.enabled') !== false) {
            $this->startSession();
        }

        $this->startPluginsAfterBoot($this->events, $this->container);

        $this->instantiateDispatchers();

        $this->hasBooted = true;

        $this->events->trigger(EventTriggers::APPLICATION_BOOT_AFTER, $this);
    }

    public function hasBooted(): bool
    {
        return $this->hasBooted;
    }

    /**
     * These dependencies have dependencies that cannot be changed upon instantiation.
     * To allow plugins to do preemptive replacement, we need to delay instantiation.
     */
    protected function instantiateDispatchers(): void
    {
        $this->responseDispatcher = $this->container->get(ResponseDispatcher::class);
        $this->routeDispatcher = $this->container->get(RouteDispatcher::class);
    }

    protected function startSession(): void
    {
        $this->events->trigger(EventTriggers::APPLICATION_SESSION_START_BEFORE);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        if ($this->config->get('parable.session.name') !== null) {
            session_name((string)$this->config->get('parable.session.name'));
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionName = session_name();

        $this->events->trigger(
            EventTriggers::APPLICATION_SESSION_START_AFTER,
            $sessionName
        );
    }

    protected function matchRoute(): ?Route
    {
        $currentRelativeUrl = $this->tools->getCurrentRelativeUrl();

        $this->events->trigger(EventTriggers::APPLICATION_ROUTE_MATCH_BEFORE, $currentRelativeUrl);

        $route = $this->router->match(
            $this->request->getMethod(),
            $currentRelativeUrl
        );

        if ($route instanceof Route) {
            $this->events->trigger(EventTriggers::APPLICATION_ROUTE_MATCH_FOUND, $route);
        } else {
            $this->events->trigger(EventTriggers::APPLICATION_ROUTE_MATCH_NOT_FOUND, $currentRelativeUrl);
        }

        $this->events->trigger(EventTriggers::APPLICATION_ROUTE_MATCH_AFTER, $route);

        return $route;
    }

    protected function dispatchResponse(): void
    {
        $this->events->trigger(EventTriggers::APPLICATION_RESPONSE_DISPATCH_BEFORE, $this->response);

        $this->responseDispatcher->dispatchWithoutTerminate($this->response);

        $this->events->trigger(EventTriggers::APPLICATION_RESPONSE_DISPATCH_AFTER, $this->response);
    }
}
