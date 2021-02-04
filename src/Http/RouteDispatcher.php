<?php declare(strict_types=1);

namespace Parable\Framework\Http;

use Parable\Di\Container;
use Parable\Event\EventManager;
use Parable\Framework\EventTriggers;
use Parable\Framework\Path;
use Parable\Http\Response;
use Parable\Http\Traits\SupportsOutputBuffers;
use Parable\Routing\Route;
use Throwable;

class RouteDispatcher
{
    use SupportsOutputBuffers;

    protected ?Route $dispatchedRoute = null;

    public function __construct(
        protected Container $container,
        protected EventManager $eventManager,
        protected Path $path,
        protected Response $response,
        protected Template $template
    ) {}

    public function dispatch(Route $route): void
    {
        try {
            $this->dispatchedRoute = $route;

            $this->eventManager->trigger(EventTriggers::ROUTE_DISPATCHER_DISPATCH_BEFORE, $route);

            $this->startOutputBuffer();

            $parameters = [];

            foreach ($route->getParameterValues()->getAll() as $value) {
                $parameters[] = $value;
            }

            if ($route->getController() !== null && $route->getAction() !== null) {
                $controller = $this->container->get($route->getController());
                $controller->{$route->getAction()}(...$parameters);
            } elseif ($route->getCallable() !== null) {
                $callable = $route->getCallable();
                $callable(...$parameters);
            }

            $content = $this->getOutputBuffer();

            $this->response->setStatusCode(200);
            $this->response->appendBody($content);

            $templatePath = $route->getMetadataValue('template');

            if ($templatePath !== null && !empty($templatePath)) {
                $this->eventManager->trigger(EventTriggers::ROUTE_DISPATCHER_DISPATCH_TEMPLATE_BEFORE, $templatePath);

                $this->startOutputBuffer();

                $this->template->setTemplatePath((string)$templatePath);
                $this->template->render();

                $this->response->appendBody($this->getOutputBuffer());

                $this->eventManager->trigger(EventTriggers::ROUTE_DISPATCHER_DISPATCH_TEMPLATE_AFTER, $templatePath);
            }

            $this->eventManager->trigger(EventTriggers::ROUTE_DISPATCHER_DISPATCH_AFTER, $route);
        } catch (Throwable $e) {
            $this->undoAllOutputBuffers();

            throw $e;
        }
    }
}
