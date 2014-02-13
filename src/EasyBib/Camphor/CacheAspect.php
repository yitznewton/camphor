<?php

namespace EasyBib\Camphor;

use Doctrine\Common\Cache\Cache;

class CacheAspect
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param string $className
     * @param array $methods
     * @throws MultipleRegistrationException
     */
    public function register($className, array $methods)
    {
        if (class_exists($this->fullCachingClassName($className))) {
            $message = sprintf('"%s" is already registered.', $className);
            throw new MultipleRegistrationException($message);
        }

        $this->verifyClass($className);
        $this->verifyMethods($className, $methods);

        $this->createCachingClass($className, $methods);
        $newClassName = $this->fullCachingClassName($className);
        $newClassName::setCache($this->cache);
    }

    /**
     * @SuppressWarnings(PHPMD.EvalExpression)
     * @param string $existingClassName
     * @param array $methods
     */
    private function createCachingClass($existingClassName, array $methods)
    {
        $code = '';

        if ($namespace = $this->extractNamespace($existingClassName)) {
            $code .= sprintf("namespace %s;\n", $namespace);
        }

        $code .= vsprintf(
            "class %s extends \\%s\n{\n",
            [
                $this->cachingClassName($existingClassName),
                $existingClassName
            ]
        );

        $code .= <<<EOF
        private \$cacheKeyGenerator;
        private static \$cache;

        public function __construct()
        {
            call_user_func_array('parent::__construct', func_get_args());
            \$this->cacheKeyGenerator = new \EasyBib\Camphor\CacheKeyGenerator();
        }

        public static function setCache(\Doctrine\Common\Cache\Cache \$cache)
        {
            self::\$cache = \$cache;
        }


EOF;

        foreach ($methods as $method) {
            $code .= $this->replacementMethod($existingClassName, $method);
        }

        $code .= "}\n";

        eval($code);
    }

    /**
     * @param string $className
     * @return string
     */
    private function extractNamespace($className)
    {
        return preg_replace('/\\\\?[^\\\\]+$/', '', $className);
    }

    /**
     * @param string $className
     * @return string
     */
    private function fullCachingClassName($className)
    {
        return $this->extractNamespace($className) . '\\' . $this->cachingClassName($className);
    }

    /**
     * @param string $className
     * @return string
     */
    private function cachingClassName($className)
    {
        return preg_replace('/^.*?([^\\\\]+)$/', 'Caching\1', $className);
    }

    /**
     * @param string $className
     * @throws NonexistentClassException
     */
    private function verifyClass($className)
    {
        if (!class_exists($className)) {
            $message = sprintf('Class "%s" does not exist.', $className);
            throw new NonexistentClassException($message);
        }
    }

    /**
     * @param string $className
     * @param array $methods
     * @throws NonexistentMethodException
     * @throws PrivateMethodException
     */
    private function verifyMethods($className, array $methods)
    {
        $reflection = new \ReflectionClass($className);

        foreach ($methods as $method) {
            if (!$reflection->hasMethod($method)) {
                $message = sprintf(
                    'Method "%s" does not exist on class "%s".',
                    $method,
                    $className
                );

                throw new NonexistentMethodException($message);
            }

            if ($reflection->getMethod($method)->isPrivate()) {
                $message = sprintf(
                    'Cannot cache method "%s" because it is private.',
                    $method
                );

                throw new PrivateMethodException($message);
            }
        }
    }

    /**
     * @param string $className
     * @param string $methodName
     * @return string
     */
    private function replacementMethod($className, $methodName)
    {
        $reflection = new \ReflectionClass($className);
        $oldMethod = $reflection->getMethod($methodName);

        $newMethod = sprintf(
            '%s function %s',
            $oldMethod->isPublic() ? 'public' : 'protected',
            $oldMethod->getName()
        );

        $newMethod .= <<<EOF
()
{
    \$args = func_get_args();
    \$key = \$this->cacheKeyGenerator->generate('$className', '$methodName', \$args);

    if (self::\$cache->contains(\$key)) {
        return self::\$cache->fetch(\$key);
    }

    \$value = call_user_func_array('parent::$methodName', \$args);
    self::\$cache->save(\$key, \$value);

    return \$value;
}

EOF;

        return $newMethod;
    }
}
