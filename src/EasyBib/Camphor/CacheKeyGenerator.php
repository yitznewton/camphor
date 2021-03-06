<?php

namespace EasyBib\Camphor;

class CacheKeyGenerator
{
    /**
     * @param string $className
     * @param string $methodName
     * @param array $args
     * @return string
     */
    public function generate($className, $methodName, array $args)
    {
        return sprintf(
            '%s|%s|%s',
            $this->stringify($className),
            $this->stringify($methodName),
            implode('|', array_map([$this, 'stringify'], $args))
        );
    }

    /**
     * @param mixed $element
     * @return string
     */
    private function stringify($element)
    {
        if (is_scalar($element)) {
            return $element;
        }

        return serialize($element);
    }
}
