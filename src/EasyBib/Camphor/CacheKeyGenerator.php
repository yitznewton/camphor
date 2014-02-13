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
        return $this->stringify(func_get_args());
    }

    private function stringify(array $elements)
    {
        return array_reduce($elements, function ($result, $element) {
            if ($result) {
                $result .= '.';
            }

            if (is_string($element)) {
                return $result . $element;
            }

            return $result . $this->stringify($element);
        }, '');
    }
}
