# Parable Rights

[![Build Status](https://travis-ci.org/parable-php/framework.svg?branch=master)](https://travis-ci.org/parable-php/framework)
[![Latest Stable Version](https://poser.pugx.org/parable-php/framework/v/stable)](https://packagist.org/packages/parable-php/framework)
[![Latest Unstable Version](https://poser.pugx.org/parable-php/framework/v/unstable)](https://packagist.org/packages/parable-php/framework)
[![License](https://poser.pugx.org/parable-php/framework/license)](https://packagist.org/packages/parable-php/framework)

Parable Rights is a simple bitmask-based rights system, using binary strings to represent rights. This allows for easy storage and fast handling.

## Install

Php 7.1+ and [composer](https://getcomposer.org) are required.

```bash
$ composer require parable-php/framework
```

## Usage

To create a rights instance with CRUD rights:

```php
$rights = new Rights();

$rights->add('create', 'read', 'update', 'delete');
```

The binary string for 4 rights is `0000` (all disabled) -> `1111` (all enabled).

```php
$rights->has('0001', 'create'); // true, since last position is 1
$rights->has('0001', 'read'); // false, since before-last is 0
```

Rights are represented in the binary string in reverse order. You can add as many rights as you want.

You can also combine two rights strings together, keeping the high values. This can be used to combine a user's individual rights and the rights set for that user's user group.

```php
$combined = $rights->combine('1000', '0001'); // $combined = 1001
```

## API

- `add(string ...$names): void` - add a right by name
- `getAll(): int[]` - get all rights (`[string => int]`)
- `getNames(): string[]` - get all rights' names
- `get(string $name): ?int` - get specific right value by name
- `can(string $provided, string $name): bool` - check if `$provided` string can take action `$name`
- `combine(string ...$rights): string` - combine two strings, keeping the highest
- `getRightsFromNames(string ...$names): string` - create binary string from rights passed 
- `getNamesFromRights(string $rights): string[]` - get enabled rights from binary string 

## Contributing

Any suggestions, bug reports or general feedback is welcome. Use github issues and pull requests, or find me over at [devvoh.com](https://devvoh.com).

## License

All Parable components are open-source software, licensed under the MIT license.
