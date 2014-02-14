<?php

namespace EasyBib\Camphor;

class ArgumentValidator
{
    public function validate($argument)
    {
        if (is_resource($argument)) {
            throw new InvalidArgumentException();
        }

        if (is_array($argument)) {
            array_map([$this, 'validate'], $argument);
        }
    }
}
