<?php

namespace EasyBib\Camphor;

class CacheAspect
{
    /**
     * @var CachingFilter
     */
    private $cachingFilter;

    /**
     * @param CachingFilter $cachingFilter
     */
    public function __construct(CachingFilter $cachingFilter)
    {
        $this->cachingFilter = $cachingFilter;
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
        $newClassName::setCachingFilter($this->cachingFilter);
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
            private \$argValidator;
            private static \$cachingFilter;

            public function __construct()
            {
                call_user_func_array('parent::__construct', func_get_args());
                \$this->cacheKeyGenerator = new \EasyBib\Camphor\CacheKeyGenerator();
                \$this->argValidator = new \EasyBib\Camphor\ArgumentValidator();
            }

            public function validateArgs(array \$args)
            {
                foreach (\$args as \$arg) {
                    \$this->argValidator->validate(\$arg);
                }
            }

            public static function setCachingFilter(\EasyBib\Camphor\CachingFilter \$cachingFilter)
            {
                self::\$cachingFilter = \$cachingFilter;
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
                \$this->validateArgs(\$args);

                \$key = \$this->cacheKeyGenerator->generate('$className', '$methodName', \$args);
                \$parentCallback = 'parent::$methodName';

                \$callback = function () use (\$parentCallback, \$args) {
                    return call_user_func_array(\$parentCallback, \$args);
                };

                return self::\$cachingFilter->applyFilter(\$key, \$callback);
            }


EOF;

        return $newMethod;
    }
}
