# Camphor: an aspect-oriented method cache for PHP

That's "**Ca**che **m**ethod for **PH**P," in case you were wondering.

This project was directly inspired by the Ruby gem
[cache_method](https://github.com/seamusabshere/cache_method)

## Installation

Because of its reliance on newer metaprogramming features, Camphor requires
**PHP 5.5 or greater**.

* [Install Composer](https://getcomposer.org/download/)
* Install Camphor's dependencies by running `composer install`

## Usage

Let's say you have a class whose method you want to cache:

```php
class Foo
{
    public function bar($param)
    {
        // do expensive stuff
        return $calculatedValue;
    }
}
```

In order to cache the method, register it with a CacheAspect. This will create
a subclass of your class in the same namespace, whose name is prefixed with
`Caching` - so in the example, `CachingFoo`. This class acts as a Proxy,
calling your method the first time its copy is called, and storing the value
in cache for future calls made with the same arguments.

```php
use Doctrine\Common\Cache\ArrayCache;

$cacheAspect = new CacheAspect(new ArrayCache());
// register the Foo class and cache the bar method
$cacheAspect->register(Foo::class, ['bar']);

$myFoo = new CachingFoo();

$firstCall  = $myFoo->bar('jimmy');  // calls Foo::bar('jimmy')
$secondCall = $myFoo->bar('jimmy');  // retrieves the value from cache
$otherCall  = $myFoo->bar('billy');  // calls Foo::bar('billy');
```

Instead of using an `ArrayCache`, you can swap in any other
[Doctrine Cache](https://github.com/doctrine/cache) implementation.

## Limitations

As of the current release, **only scalar arguments** are supported.
