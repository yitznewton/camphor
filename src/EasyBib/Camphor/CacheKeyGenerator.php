<?php

namespace EasyBib\Camphor;

class CacheKeyGenerator
{
    /**
     * @param $className
     * @param $methodName
     * @param array $args
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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

    private function stringify($element)
    {
        if (is_scalar($element)) {
            return $element;
        }

        return serialize($element);
    }
}
