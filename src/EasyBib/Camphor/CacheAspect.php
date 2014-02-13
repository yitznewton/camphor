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
     * @param string $className
     * @param array $methods
     * @SuppressWarnings(PHPMD.EvalExpression)
     */
    private function createCachingClass($className, array $methods)
    {
        $code = '';

        if ($namespace = $this->extractNamespace($className)) {
            $code .= sprintf("namespace %s;\n", $namespace);
        }

        $code .= vsprintf(
            "class %s extends \\%s {\n",
            [
                $this->cachingClassName($className),
                $className
            ]
        );

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

        $newMethod .= "() {}\n";

        return $newMethod;
    }
}
