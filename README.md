# CacheWithLock

Package for [Laravel framework](https://laravel.com/) - 
overrides `remember` method of Laravel Cache using lock mechanism
to avoid multiply generation of the same data (as a result of 
race condition) when cache becomes invalid.

## Requirements

*   PHP >= 7.4
*   Laravel >= 7.0

## Install

Install (or update) package via [composer](http://getcomposer.org/):

```bash
composer require enikeishik/cachewithlock
```

Make sure autoload will be changed:

```bash
composer dump-autoload
```

Publish package via artisan:

```bash
php artisan vendor:publish --provider="Enikeishik\CacheWithLock\ServiceProvider"
```

This command copy configuration file into corresponding project folder.

## Usage

Package service provider contains an `extend` call to override Laravel Cache class.
So there is no need to make any changes in code.

Overriding can be disabled in package configuration.

Without overriding Laravel Cache use `CacheWithLock` facade:

```php
use CacheWithLock;

...

$value = CacheWithLock::remember(...);

...

```
