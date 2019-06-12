<?php declare(strict_types=1);

namespace Parable\Framework;

use Parable\Di\Container;
use Parable\Event\EventManager;
use Parable\Framework\Http\RouteDispatcher;
use Parable\Framework\Http\Tools;
use Parable\Framework\Plugins\PluginManager;
use Parable\GetSet\GetCollection;
use Parable\Http\Request;
use Parable\Http\RequestFactory;
use Parable\Http\Response;
use Parable\Http\ResponseDispatcher;
use Parable\Routing\Route;
use Parable\Routing\Router;

class Application
{
    public const VERSION = '2.0.0-alpha';

    public const PLUGIN_BEFORE_BOOT = 'plugin_before_boot';
    public const PLUGIN_AFTER_BOOT = 'plugin_after_boot';

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @var GetCollection
     */
    protected $get;

    /**
     * @var Path
     */
    protected $path;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var ResponseDispatcher
     */
    protected $responseDispatcher;

    /**
     * @var RouteDispatcher
     */
    protected $routeDispatcher;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Tools
     */
    protected $tools;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var bool
     */
    protected $hasBooted = false;

    public function __construct(
        Container $container,
        EventManager $eventManager
    ) {
        if (Context::isCli()) {
            throw new Exception('Application cannot be used in CLI context.');
        }

        $this->container = $container;
        $this->eventManager = $eventManager;
    }

    public function run(): void
    {
        $this->eventManager->trigger(EventTriggers::APPLICATION_RUN_BEFORE, $this);

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

        $this->eventManager->trigger(EventTriggers::APPLICATION_RUN_AFTER, $this);
    }

    public function boot(): void
    {
        $this->eventManager->trigger(EventTriggers::APPLICATION_BOOT_BEFORE, $this);

        if ($this->hasBooted()) {
            throw new Exception('App has already booted.');
        }

        $this->startPluginsBeforeBoot();

        // We do this after the plugins to allow plugins to change dependencies before they're used.
        $this->instantiateDependencies();

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

        $this->startPluginsAfterBoot();

        $this->hasBooted = true;

        $this->eventManager->trigger(EventTriggers::APPLICATION_BOOT_AFTER, $this);
    }

    public function hasBooted(): bool
    {
        return $this->hasBooted;
    }

    protected function instantiateDependencies(): void
    {
        $this->config = $this->container->get(Config::class);
        $this->get = $this->container->get(GetCollection::class);;
        $this->path = $this->container->get(Path::class);;
        $this->response = $this->container->get(Response::class);;
        $this->responseDispatcher = $this->container->get(ResponseDispatcher::class);;
        $this->routeDispatcher = $this->container->get(RouteDispatcher::class);;
        $this->router = $this->container->get(Router::class);;

        $this->request = RequestFactory::createFromServer();

        $this->container->store($this->request);

        // Tools requires the Request, so we build this one manually
        $this->tools = $this->container->get(Tools::class);
    }

    protected function startPluginsBeforeBoot(): void
    {
        $this->eventManager->trigger(EventTriggers::APPLICATION_PLUGINS_START_BEFORE_BOOT_BEFORE);

        PluginManager::startPlugins(self::PLUGIN_BEFORE_BOOT, $this->container);

        $this->eventManager->trigger(EventTriggers::APPLICATION_PLUGINS_START_BEFORE_BOOT_AFTER);
    }

    protected function startPluginsAfterBoot(): void
    {
        $this->eventManager->trigger(EventTriggers::APPLICATION_PLUGINS_START_AFTER_BOOT_BEFORE);

        PluginManager::startPlugins(self::PLUGIN_AFTER_BOOT, $this->container);

        $this->eventManager->trigger(EventTriggers::APPLICATION_PLUGINS_START_AFTER_BOOT_AFTER);
    }

    protected function startSession(): void
    {
        $this->eventManager->trigger(EventTriggers::APPLICATION_SESSION_START_BEFORE);

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

        $this->eventManager->trigger(
            EventTriggers::APPLICATION_SESSION_START_AFTER,
            $sessionName
        );
    }

    protected function setupDatabaseFromConfig(Config $config): void
    {
        $this->eventManager->trigger(EventTriggers::APPLICATION_INIT_DATABASE_BEFORE);

        $databaseFactory = new DatabaseFactory();
        $database = $databaseFactory->createFromConfig($config);

        $this->container->store($database);

        $database->connect();

        $this->eventManager->trigger(EventTriggers::APPLICATION_INIT_DATABASE_AFTER, $database);
    }

    protected function setDefaultTimeZone(string $timezone): void
    {
        $this->eventManager->trigger(EventTriggers::APPLICATION_SET_DEFAULT_TIMEZONE_BEFORE, $timezone);

        date_default_timezone_set($timezone);

        $currentlySetTimezone = date_default_timezone_get();

        $this->eventManager->trigger(
            EventTriggers::APPLICATION_SET_DEFAULT_TIMEZONE_AFTER,
            $currentlySetTimezone
        );
    }

    protected function matchRoute(): ?Route
    {
        $currentRelativeUrl = $this->tools->getCurrentRelativeUrl();

        $this->eventManager->trigger(EventTriggers::APPLICATION_ROUTE_MATCH_BEFORE, $currentRelativeUrl);

        $route = $this->router->match(
            $this->request->getMethod(),
            $currentRelativeUrl
        );

        if ($route instanceof Route) {
            $this->eventManager->trigger(EventTriggers::APPLICATION_ROUTE_MATCH_FOUND, $route);
        } else {
            $this->eventManager->trigger(EventTriggers::APPLICATION_ROUTE_MATCH_NOT_FOUND, $currentRelativeUrl);
        }

        $this->eventManager->trigger(EventTriggers::APPLICATION_ROUTE_MATCH_AFTER, $route);

        return $route;
    }

    protected function dispatchResponse(): void
    {
        $this->eventManager->trigger(EventTriggers::APPLICATION_RESPONSE_DISPATCH_BEFORE, $this->response);

        $this->responseDispatcher->dispatch($this->response);

        $this->eventManager->trigger(EventTriggers::APPLICATION_RESPONSE_DISPATCH_AFTER, $this->response);
    }

    protected function enableErrorReporting(): void
    {
        ini_set('display_errors', '1');
        error_reporting($this->config->get('parable.debug.levels') ?? E_ALL);
    }

    protected function disableErrorReporting(): void
    {
        if (ini_get('display_errors') !== '1') {
            return;
        }

        ini_set('display_errors', '0');
        error_reporting(E_ALL | ~E_DEPRECATED);
    }
}
