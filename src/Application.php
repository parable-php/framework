<?php declare(strict_types=1);

namespace Parable\Framework;

use Parable\Di\Container;
use Parable\Event\EventManager;
use Parable\Framework\Http\RouteDispatcher;
use Parable\Framework\Http\Tools;
use Parable\Framework\Plugins\PluginManager;
use Parable\GetSet\GetCollection;
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
    protected $getCollection;

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
     * @var bool
     */
    protected $hasBooted = false;

    public function __construct(
        Container $container,
        Config $config,
        EventManager $eventManager,
        GetCollection $getCollection,
        Path $path,
        Response $response,
        ResponseDispatcher $responseDispatcher,
        RouteDispatcher $routeDispatcher,
        Router $router
    ) {
        if (Context::isCli()) {
            throw new Exception('Cannot boot Application in cli context.');
        }

        $this->container = $container;
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->getCollection = $getCollection;
        $this->path = $path;
        $this->response = $response;
        $this->responseDispatcher = $responseDispatcher;
        $this->routeDispatcher = $routeDispatcher;
        $this->router = $router;

        $this->request = RequestFactory::createFromServer();

        $container->store($this->request);

        // Tools requires the Request, so we build this one manually
        $this->tools = $container->get(Tools::class);
    }

    public function run(): void
    {
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
    }

    public function boot(): void
    {
        $this->eventManager->trigger(EventTriggers::APPLICATION_BOOT_BEFORE, $this);

        if ($this->hasBooted) {
            throw new \Parable\Framework\Exception('App has already booted.');
        }

        $this->startPluginsBeforeBoot();

        if ($this->config->get('parable.debug.enabled') === true) {
            $this->enableErrorReporting();
        }

        if ($this->config->has('parable.database.type')) {
            $this->setupDatabase();
        }

        if ($this->config->get('parable.session.enabled') === true) {
            $this->startSession();
        }

        if ($this->config->has('parable.default-timezone')) {
            $this->setDefaultTimezone();
        }

        $this->startPluginsAfterBoot();

        $this->eventManager->trigger(EventTriggers::APPLICATION_BOOT_AFTER, $this);

        $this->hasBooted = true;
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

        if ($this->config->get('parable.session.name') !== null) {
            session_name((string)$this->config->get('parable.session.name'));
        }

        session_start();

        $this->eventManager->trigger(EventTriggers::APPLICATION_SESSION_START_AFTER, session_name());
    }

    protected function setupDatabase(): void
    {
        $this->eventManager->trigger(EventTriggers::APPLICATION_INIT_DATABASE_BEFORE);

        $databaseFactory = new DatabaseFactory();
        $database = $databaseFactory->createFromConfig($this->config);

        $this->container->store($database);

        $database->connect();

        $this->eventManager->trigger(EventTriggers::APPLICATION_INIT_DATABASE_AFTER, $database);
    }

    protected function setDefaultTimeZone(): void
    {
        $timezone = $this->config->get('parable.default-timezone');

        $this->eventManager->trigger(EventTriggers::APPLICATION_SET_DEFAULT_TIMEZONE_BEFORE, $timezone);

        date_default_timezone_set($timezone);

        $this->eventManager->trigger(
            EventTriggers::APPLICATION_SET_DEFAULT_TIMEZONE_AFTER,
            date_default_timezone_get()
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
            $this->eventManager->trigger(EventTriggers::APPLICATION_ROUTE_FOUND, $route);
        } else {
            $this->eventManager->trigger(EventTriggers::APPLICATION_ROUTE_NOT_FOUND, $currentRelativeUrl);
        }

        $this->eventManager->trigger(EventTriggers::APPLICATION_ROUTE_MATCH_AFTER, $route);

        return $route;
    }

    protected function dispatchResponse(): void
    {
        $this->eventManager->trigger(EventTriggers::APPLICATION_RESPONSE_SEND_BEFORE, $this->response);

        $this->responseDispatcher->dispatch($this->response);

        $this->eventManager->trigger(EventTriggers::APPLICATION_RESPONSE_SEND_AFTER, $this->response);
    }

    protected function enableErrorReporting(): void
    {
        ini_set('display_errors', '1');
        error_reporting($this->config->get('parable.debug.levels') ?? E_ALL);
    }

    protected function disbleErrorReporting(): void
    {
        ini_set('display_errors', '0');
        error_reporting(E_ALL | ~E_DEPRECATED);
    }
}
