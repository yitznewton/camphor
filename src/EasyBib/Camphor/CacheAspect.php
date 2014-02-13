<?php

namespace EasyBib\Camphor;

class CacheAspect
{
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
    }

    /**
     * @SuppressWarnings(PHPMD.EvalExpression)
     * @param string $className
     * @param array $methods
     */
    private function createCachingClass($className, array $methods)
    {
        $code = '';

        if ($namespace = $this->extractNamespace($className)) {
            $code .= sprintf("namespace %s;\n", $namespace);
        }

        $code .= vsprintf(
            "class %s extends \\%s\n{\n",
            [
                $this->cachingClassName($className),
                $className
            ]
        );

        $code .= <<<EOF
        private \$cache = [];
        private \$cacheKeyGenerator;

        public function __construct()
        {
            call_user_func_array('parent::__construct', func_get_args());
            \$this->cacheKeyGenerator = new \EasyBib\Camphor\CacheKeyGenerator();
        }


EOF;

        foreach ($methods as $method) {
            $code .= $this->replacementMethod($className, $method);
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

    if (array_key_exists(\$key, \$this->cache)) {
        return \$this->cache[\$key];
    }

    return \$this->cache[\$key] = call_user_func_array('parent::$methodName', \$args);
}

EOF;

        return $newMethod;
    }
}
