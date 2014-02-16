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

Let's say you have a class, and you want to cache one or more of its methods.

```php
class Foo
{
    public function bar($param)
    {
        // do lots of
        // expensive stuff
        // ...

        return $calculatedValue;
    }
}
```

In order to cache the method, register it with a `CacheAspect`. This will create
a subclass of your class in the same namespace, whose name is prefixed with
`Caching` - so in the example, `CachingFoo`. This class, in conjunction with
 Camphor's `CachingFilter` class, acts as a Proxy,
calling your method the first time its copy is called, and storing the value
in cache for future calls made with the same arguments.

```php
use EasyBib\Camphor\CacheAspect;
use EasyBib\Camphor\CachingFilter;

$cachingFilter = new CachingFilter();
$cacheAspect = new CacheAspect($cachingFilter);
// register the Foo class and cache the bar method
$cacheAspect->register(Foo::class, ['bar']);

$myFoo = new CachingFoo();

$firstCall  = $myFoo->bar('jimmy');  // calls Foo::bar('jimmy')
$secondCall = $myFoo->bar('jimmy');  // retrieves the value from cache
$otherCall  = $myFoo->bar('billy');  // calls Foo::bar('billy');
```

Instead of using the default `ArrayCache`, you can swap in any other
[Doctrine Cache](https://github.com/doctrine/cache) implementation by
instantiating it and passing it in the `CachingFilter` constructor:

```php
use Doctrine\Common\Cache\RedisCache;
use EasyBib\Camphor\CachingFilter;

$redisCache = new RedisCache();
$cachingFilter = new CachingFilter($redisCache);
// etc.
```

## Limitations

* By default, the pipe symbol `|` is used as a delimiter in creating cache keys.
  If your arguments may include pipe symbols, you will need to set a different
  key in order to be certain that there are no cache key collisions. There is
  no support for this in the current release.

* As of the current release, only methods with exclusively
  [PHP-serializable](http://www.php.net/manual/en/function.serialize.php)
  arguments are supported.

* Since Camphor's generated classes are built at runtime, they are not able to
  benefit from opcode caches. Use of Camphor assumes that your system
  architecture is capable of working with `eval()`'ed code, and that the
  expense of running your actual code repeatedly is greater than the expense
  of generating the caching subclass on the fly.
