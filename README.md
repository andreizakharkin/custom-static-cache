# Custom Static Cache


Custom static cache


## Laravel compatibility

Laravel 5 is released!!

 Laravel   | custom-static-caching
:----------|:----------
 5.2.x     | dev-master

## Installation

### Composer

Download via composer.

    composer require andreizakharkin/custom-static-caching:dev-master

### Laravel

Register the service provider with your application.

Open `config/app.php` and find the `providers` key. Add `CustomStaticCacheServiceProvider` to the array.

```php
	...
	Zakharkin\CustomStaticCache\CustomStaticCacheServiceProvider::class,
	...
```

Publish vendor config

```text
	php artisan vendor:publish
```

## Usage

There are two main functions that helps you to use static caching.

`checkAndShow()` - function that can check and render the cache file

`save()` - function that saves a cache file

You can use this functions in anywhere places of your application. The only rule is need to be followed is that the `checkAndShow()` function must be triggered, after the autoload is completed

## Example usage in public/index.php

```php

	...
	
	require __DIR__.'/../bootstrap/autoload.php';
	
	\Zakharkin\CustomStaticCache\CustomStaticCache::getInstance()->checkAndShow();
	
	...
	
	$kernel->terminate($request, $response);
	
	\Zakharkin\CustomStaticCache\CustomStaticCache::getInstance()->save();

```
