<?php

namespace EasyBib\Camphor;

class ArgumentValidator
{
    public function validate($argument)
    {
        if (!is_scalar($argument)) {
            throw new NonscalarArgumentException('Argument is not scalar');
        }
    }
}
