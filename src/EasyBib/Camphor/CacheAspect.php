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

        $this->verifyMethods($className, $methods);

        $this->createCachingClass($className, $methods);
    }

    /**
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
            'class %s extends \\%s {}',
            [
                $this->cachingClassName($className),
                $className
            ]
        );

        eval($code);
    }

    private function extractNamespace($className)
    {
        return preg_replace('/\\\\?[^\\\\]+$/', '', $className);
    }

    private function fullCachingClassName($className)
    {
        return $this->extractNamespace($className) . '\\' . $this->cachingClassName($className);
    }

    private function cachingClassName($className)
    {
        return preg_replace('/^.*?([^\\\\]+)$/', 'Caching\1', $className);
    }

    private function verifyMethods($className, array $methods)
    {
        $reflection = new \ReflectionClass($className);

        foreach ($methods as $method) {
            if (!$reflection->hasMethod($method)) {
                $message = sprintf(
                    'Method "%s" does not exist on class "%s"',
                    $method,
                    $className
                );

                throw new NonexistentMethodException($message);
            }
        }
    }
}
