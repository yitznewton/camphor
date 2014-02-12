<?php

namespace EasyBib\Tests\Camphor\Mocks;

class DataContainer
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @var bool
     */
    private $alreadyCalled = false;

    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        if ($this->alreadyCalled) {
            return null;
        }

        $this->alreadyCalled = true;

        return $this->value;
    }
}
