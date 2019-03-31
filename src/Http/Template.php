<?php declare(strict_types=1);

namespace Parable\Framework\Http;

use Parable\Di\Container;
use Parable\Framework\Exception;
use Parable\Framework\Path;
use Parable\Http\Response;
use Parable\Http\Traits\SupportsOutputBuffers;

/**
 * @property-read \Parable\Event\EventManager $eventManager
 * @property-read \Parable\Framework\Path $path
 * @property-read \Parable\Framework\Config $config
 * @property-read \Parable\GetSet\CookieCollection $cookieCollection
 * @property-read \Parable\GetSet\DataCollection $dataCollection
 * @property-read \Parable\GetSet\FilesCollection $filesCollection
 * @property-read \Parable\GetSet\GetCollection $getCollection
 * @property-read \Parable\GetSet\InputStreamCollection $inputStreamCollection
 * @property-read \Parable\GetSet\PostCollection $postCollection
 * @property-read \Parable\GetSet\ServerCollection $serverCollection
 * @property-read \Parable\GetSet\SessionCollection $sessionCollection
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
     * @var Response
     */
    protected $response;

    /**
     * @var string
     */
    protected $templatePath;

    /**
     * @var array
     */
    protected $classes = [];

    public function __construct(
        Container $container,
        Path $path,
        Response $response
    ) {
        $this->container = $container;
        $this->path = $path;
        $this->response = $response;

        $this->registerClassesFromMagicProperties();
    }

    public function registerClass(string $propertyName, string $className): void
    {
        $className = '\\' . ltrim($className, '\\');
        $this->classes[$propertyName] = $className;
    }

    public function setTemplatePath(string $templatePath): void
    {
        $this->templatePath = $templatePath;
    }

    public function partial($templatePath): string
    {
        $this->startOutputBuffer();

        $this->loadTemplatePath($templatePath);

        return $this->getOutputBuffer();
    }

    public function render(): void
    {
        $this->loadTemplatePath($this->templatePath);
    }

    protected function registerClassesFromMagicProperties(): void
    {
        $reflection = new \ReflectionClass(self::class);
        $docComment = $reflection->getDocComment();
        $annotatedProperties = $docComment ? explode(PHP_EOL, $docComment) : [];

        foreach ($annotatedProperties as $magicProperty) {
            if (strpos($magicProperty, '@property-read') === false) {
                continue;
            }

            $partsString = trim(str_replace('* @property-read', '', $magicProperty));

            $parts = explode('$', $partsString);

            [$className, $property] = $parts;

            $this->registerClass(trim($property), trim($className));
        }
    }

    protected function loadTemplatePath(string $templatePath): void
    {
        require $templatePath;
    }

    public function __get($propertyName)
    {
        if (!isset($this->classes[$propertyName])) {
            throw new Exception(sprintf(
                "Property '%s' was not registered on the Template/",
                $propertyName
            ));
        }

        return $this->container->get($this->classes[$propertyName]);
    }
}
