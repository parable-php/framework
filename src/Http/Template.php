<?php declare(strict_types=1);

namespace Parable\Framework\Http;

use Parable\Di\Container;
use Parable\Framework\Exception;
use Parable\Framework\Path;
use Parable\Http\Traits\SupportsOutputBuffers;

/**
 * @property-read \Parable\Di\Container $container
 * @property-read \Parable\Event\EventManager $eventManager
 * @property-read \Parable\Framework\Path $path
 * @property-read \Parable\Framework\Config $config
 * @property-read \Parable\Framework\Http\Tools $tools
 * @property-read \Parable\GetSet\CookieCollection $cookie
 * @property-read \Parable\GetSet\DataCollection $data
 * @property-read \Parable\GetSet\FilesCollection $files
 * @property-read \Parable\GetSet\GetCollection $get
 * @property-read \Parable\GetSet\InputStreamCollection $inputStream
 * @property-read \Parable\GetSet\PostCollection $post
 * @property-read \Parable\GetSet\ServerCollection $server
 * @property-read \Parable\GetSet\SessionCollection $session
 * @property-read \Parable\Http\Request $request
 * @property-read \Parable\Http\Response $response
 * @property-read \Parable\Routing\Router $router
 */
class Template
{
    use SupportsOutputBuffers;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Path
     */
    protected $path;

    /**
     * @var string
     */
    protected $templatePath;

    /**
     * @var string|null
     */
    protected $templateRoot;

    public function __construct(
        Container $container,
        Path $path
    ) {
        $this->container = $container;
        $this->path = $path;
    }

    public function setTemplateRoot(string $templateRoot): void
    {
        $this->templateRoot = $templateRoot;
    }

    public function setTemplatePath(string $templatePath): void
    {
        $this->templatePath = $templatePath;
    }

    public function partial(string $templatePath): string
    {
        $this->startOutputBuffer();

        try {
            $this->loadTemplatePath($templatePath);
        } catch (Exception $e) {
            $this->undoOutputBuffer();

            throw $e;
        }

        return $this->getOutputBuffer();
    }

    public function render(): void
    {
        if (!$this->templatePath) {
            return;
        }

        $this->loadTemplatePath($this->templatePath);
    }

    protected function loadTemplatePath(string $templatePath): void
    {
        $fullPath = $this->buildTemplatePathFromRoot($templatePath);

        if (file_exists($fullPath)) {
            require $fullPath;
            return;
        }

        if (file_exists($this->path->getPath($fullPath))) {
            require $this->path->getPath($fullPath);
            return;
        }

        throw new Exception(sprintf(
            "Template path '%s' could not be loaded.",
            $fullPath
        ));
    }

    protected function buildTemplatePathFromRoot(string $templatePath): string
    {
        if ($this->templateRoot === null) {
            return $templatePath;
        }

        return rtrim($this->templateRoot, '/') . '/' . $templatePath;
    }

    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        $reflection = new \ReflectionClass(self::class);
        $docComment = $reflection->getDocComment();

        $annotatedProperties = explode(PHP_EOL, $docComment);
        $matchedProperty = null;

        foreach ($annotatedProperties as $annotatedProperty) {
            if (strpos($annotatedProperty, '$' . $name) !== false) {
                $matchedProperty = str_replace(' * @property-read ', '', $annotatedProperty);
                $matchedProperty = str_replace('$' . $name, '', $matchedProperty);
                break;
            }
        }

        if ($matchedProperty === null) {
            throw new Exception(sprintf(
                "Could not load property '%s' through Template.",
                $name
            ));
        }

        return $this->container->get((string)$matchedProperty);
    }
}
