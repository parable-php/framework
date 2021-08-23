# Parable PHP Framework

[![Workflow Status](https://github.com/parable-php/framework/workflows/Tests/badge.svg)](https://github.com/parable-php/framework/actions?query=workflow%3ATests)
[![Latest Stable Version](https://poser.pugx.org/parable-php/framework/v/stable)](https://packagist.org/packages/parable-php/framework)
[![Latest Unstable Version](https://poser.pugx.org/parable-php/framework/v/unstable)](https://packagist.org/packages/parable-php/framework)
[![License](https://poser.pugx.org/parable-php/framework/license)](https://packagist.org/packages/parable-php/framework)

Parable is a PHP framework with two goals: it's just enough, and it doesn't enforce a way to build.

## Install

Php 8.0+ and [composer](https://getcomposer.org) are required.

```bash
$ composer require parable-php/parable
```

And to set up parable, run the following and follow the interactive questions:

```bash
$ vendor/bin/parable install
```

## Usage

After installation (and you chose to install the example app) you'll have an example application to look at.

Parable 2.0 is based around plugins to arrange everything. It's not a framework that makes you do things a certain way outside of this.

If you want to set up routes? `RoutesPlugin`. Set up the configuration? `ConfigPlugin`. As long as you add these to `Boot.php` they'll be loaded at the appropriate time.

`Application::PLUGIN_BEFORE_BOOT` should contain plugins that need to be loaded _before_ the database or sessions are loaded. `ConfigPlugin`, for example, should be here so you can set up the database.
`Application::PLUGIN_AFTER_BOOT` is for plugins that require a database, for example.

## Example `ConfigPlugin`

Anything within the config namespace `parable` is used by the `Application` class to set up specific things.
You don't have to use these (this example file shows all possible values) but you can if it's faster/easier.

In the example below, `yourstuff` is just an example of adding your own config values to the `Config` instance used throughout the application. Feel free to add whatever you want here!

```php
class ConfigPlugin implements PluginInterface
{
    public function __construct(
        protected Config $config
    ) {}

    public function run(): void
    {
        $this->config->setMany([
            'parable' => [
                'default-timezone' => 'Europe/Amsterdam',
                'database' => [
                    'type' => Database::TYPE_MYSQL,
                    'host' => 'localhost',
                    'username' => 'username',
                    'password' => 'password',
                    'database' => 'database',
                    'charSet' => 'utf8mb4',
                    'port' => 40673,
                    // all other values, see https://github.com/parable-php/orm/blob/master/README.md for more
                ],
                'session' => [
                    'enabled' => true,
                    'name' => 'app_session_name',
                ],
                'debug' => [
                    'enabled' => (bool)getenv('DEBUG_ENABLED'),
                    'levels' => E_ALL | ~E_DEPRECATED,
                ],
            ],
            'yourstuff' => [
                'extensions' => [
                    CleanerExtension::class,
                ],
            ],
        ]);
    }
}
```

## Example `RoutingPlugin`

To set up routing, just add a `RoutingPlugin` and place it either in `PLUGIN_BEFORE_BOOT` or `PLUGIN_AFTER_BOOT` depending on whether you need the session/database to be active first.

For full information on `parable-php/routing`, read the [README.md](https://github.com/parable-php/routing) of that package.

```php
class RoutingPlugin implements PluginInterface
{
    public function __construct(
        protected Router $router,
        protected Path $path
    ) {}

    public function run(): void
    {
        $this->router->add(
            ['GET'],
            'site-index',
            '/',
            [SiteController::class, 'indexAction'],
            ['template' => $this->path->getPath('src/Templates/Site/index.phtml')]
        );
    }
}
```

Being able to set `template` is something specific to Parable 2.0.0, `parable-php/routing` by default doesn't understand templates, so it's being passed as metadata, and the framework itself can deal with the `template` metadata.

The template files are expected to just be `php` files (but named `phtml` here to indicate it's a template). See `welcome.phtml` in the example app to see how to use it.

By setting this at the top of the file, you get access to a lot of built-in features:

```php
/** @var \Parable\Framework\Http\Template $this */
```

See the top-most doc-block of `Template.php` to see what's available, but you can do anything from accessing the Di Container (`$this->container`) to events (`$this-events`).

A question I've gotten regularly is 'how do I pass data from a controller to a template/view?'.

Well, my dear friend, it's easy. In the controller:

```php
public function __construct(protected DataCollection $dataCollection) {}

public function someAction() {
    $this->dataCollection->set('viewData', [
        'value' => 'this is value',
        'bool' => true,    
    ]);
}
```

And in the template, if you've added `@var \Parable\Framework\Http\Template $this` like I suggested you do? Easy!

```php
echo 'Value is ' . $this->data->get('viewData.value');
```

The `DataCollection` from `parable-php/getset` is specifically intended for free-form data storing and sharing across files, so it's perfect for this.

Note that you don't need either controllers OR templates! You can also just pass an anonymous function in, which can be perfect for small and simple REST APIs:

```php
$this->router->add(
    ['GET'],
    'api-index',
    '/api',
    static function () {
        echo json_encode([
            'status' => 'success',
        ]);
    },
);
```

## Packages used in `parable-php/framework`

Check the below packages for any questions you might have about how to approach them. Their behavior is fully available to you
as part of the framework and Parable 2.0 doesn't do anything special to stop you or wrap it in weird ways. Mostly what's been
added only serves to do setup as part of the `Config` flow, none of which is required.

- [parable-php/console](https://github.com/parable-php/console)
- [parable-php/di](https://github.com/parable-php/di)
- [parable-php/event](https://github.com/parable-php/event)
- [parable-php/getset](https://github.com/parable-php/getset)
- [parable-php/orm](https://github.com/parable-php/orm)
- [parable-php/routing](https://github.com/parable-php/routing)
- [parable-php/http](https://github.com/parable-php/http)

## Thank youses

I want to thank the following people for inspiring, challenging and brainstorming with me about ideas and how to make Parable the best
framework I never found anywhere else:

- [Dave van der Brugge](https://github.com/dmvdbrugge) for his almost insane eye for detail and asking 'why?' so often it made me cry once.
- [Thijs Riezebeek](https://github.com/Thijs-Riezebeek) for being my braining sparring partner whether he wants to or not. Will never ever ever not challenge me on _every single decision ever_. Which is good!
- [Jerry van Kooten](https://github.com/jerry1970) for always going well beyond the knowledge I'd expect him to have and testing my stuff out. Also for actually creating PRs when he hated something, improving a few packages massively.
- [Lucas Grossi](https://github.com/lgrossi) for choosing to go with Parable's alpha version for an actual professional project, forcing me to release 2.0.0 because otherwise it'd never be approved.

## Contributing

Any suggestions, bug reports or general feedback is welcome. Use github issues and pull requests, or find me over at [devvoh.com](https://devvoh.com).

## License

All Parable components are open-source software, licensed under the MIT license.
