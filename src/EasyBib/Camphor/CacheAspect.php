<?php

namespace EasyBib\Camphor;

class CacheAspect
{
    public function register($className, array $methods)
    {
        // do something with these params to avoid a PHPMD error
        echo $className;
        echo $methods;
    }
}
