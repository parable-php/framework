<?php declare(strict_types=1);

namespace Parable\Framework\Tests;

use Parable\Di\Container;
use Parable\Event\Events;
use Parable\Framework\Config;
use Parable\Framework\FrameworkException;
use Parable\Framework\Http\Template;
use Parable\Framework\Http\Tools;
use Parable\Framework\Path;
use Parable\GetSet\CookieCollection;
use Parable\GetSet\DataCollection;
use Parable\GetSet\FilesCollection;
use Parable\GetSet\GetCollection;
use Parable\GetSet\InputStreamCollection;
use Parable\GetSet\PostCollection;
use Parable\GetSet\ServerCollection;
use Parable\GetSet\SessionCollection;
use Parable\Http\Request;
use Parable\Http\Response;
use Parable\Routing\Router;

class TemplateTest extends AbstractTestCase
{
    protected Template $template;

    public function setUp(): void
    {
        parent::setUp();

        $this->template = new class (...$this->container->getDependenciesFor(Template::class)) extends Template
        {
            public function getTemplateRoot(): ?string
            {
                return $this->templateRoot;
            }

            public function getTemplatePath(): ?string
            {
                return $this->templatePath;
            }
        };

        $this->container->store(new Request('GET', 'https://test.dev/page/home'));
    }

    public function testTemplateRoot(): void
    {
        self::assertNull($this->template->getTemplateRoot());
        self::assertNull($this->template->getTemplatePath());

        $this->template->setTemplateRoot('/tmp');
        $this->template->setTemplatePath('template.phtml');

        self::assertSame('/tmp', $this->template->getTemplateRoot());
        self::assertSame('template.phtml', $this->template->getTemplatePath());
    }

    public function testPartial(): void
    {
        $content = $this->template->partial(__DIR__ . '/Classes/template.phtml');

        self::assertSame('This is a render of ' . __DIR__ . '/Classes/template.phtml', trim($content));
    }

    public function testPartialThrowsExceptionOnUnknownPath(): void
    {
        $this->expectException(FrameworkException::class);
        $this->expectExceptionMessage("Template path 'nope' could not be loaded.");

        $this->template->partial('nope');
    }

    public function testRenderWithOnlyPathProvided(): void
    {
        $this->template->setTemplatePath('Classes/template.phtml');

        $this->template->startOutputBuffer();

        $this->template->render();

        self::assertSame(
            'This is a render of ' . __DIR__ . '/Classes/template.phtml',
            trim($this->template->getOutputBuffer())
        );
    }

    public function testRenderWithRootAndPathProvided(): void
    {
        $this->template->setTemplateRoot(__DIR__);
        $this->template->setTemplatePath('Classes/template.phtml');

        $this->template->startOutputBuffer();

        $this->template->render();

        self::assertSame(
            'This is a render of ' . __DIR__ . '/Classes/template.phtml',
            trim($this->template->getOutputBuffer())
        );
    }

    public function testRenderWithoutTemplatePathDoesNothing(): void
    {
        $this->template->startOutputBuffer();

        $this->template->render();

        self::assertEmpty($this->template->getOutputBuffer());
    }

    public function testRenderWithoutValidTemplatePathThrowsException(): void
    {
        $this->expectException(FrameworkException::class);
        $this->expectExceptionMessage("Template path 'nope' could not be loaded.");

        $this->template->setTemplatePath('nope');

        $this->template->render();
    }

    public function testPropertyNotAvailableOnTemplateThrowsException(): void
    {
        $this->expectException(FrameworkException::class);
        $this->expectExceptionMessage("Could not load property 'nope' through Template.");

        $this->template->nope;
    }

    /**
     * @dataProvider dpTemplateProperties
     */
    public function testAllPropertiesAvailableToTemplate(string $className, string $propertyName): void
    {
        self::assertInstanceOf($className, $this->template->{$propertyName});
    }

    public function dpTemplateProperties(): array
    {
        return [
            'container' => [
                Container::class,
                'container',
            ],
            'events' => [
                Events::class,
                'events',
            ],
            'path' => [
                Path::class,
                'path',
            ],
            'config' => [
                Config::class,
                'config',
            ],
            'tools' => [
                Tools::class,
                'tools',
            ],
            'cookie' => [
                CookieCollection::class,
                'cookie',
            ],
            'data' => [
                DataCollection::class,
                'data',
            ],
            'files' => [
                FilesCollection::class,
                'files',
            ],
            'get' => [
                GetCollection::class,
                'get',
            ],
            'inputStream' => [
                InputStreamCollection::class,
                'inputStream',
            ],
            'post' => [
                PostCollection::class,
                'post',
            ],
            'server' => [
                ServerCollection::class,
                'server',
            ],
            'session' => [
                SessionCollection::class,
                'session',
            ],
            'request' => [
                Request::class,
                'request',
            ],
            'response' => [
                Response::class,
                'response',
            ],
            'router' => [
                Router::class,
                'router',
            ],
        ];
    }
}
