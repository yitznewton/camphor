<?php

namespace EasyBib\Camphor;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;

class CachingProxy
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache = null)
    {
        if (!$cache) {
            $cache = new ArrayCache();
        }

        $this->cache = $cache;
    }

    /**
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    public function applyProxy($key, callable $callback)
    {
        if ($this->cache->contains($key)) {
            return $this->cache->fetch($key);
        }

        $value = $callback();
        $this->cache->save($key, $value);

        return $value;
    }

    public function reset()
    {
        $this->cache->deleteAll();
    }
}
