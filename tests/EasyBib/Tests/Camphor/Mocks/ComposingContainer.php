<?php

namespace EasyBib\Tests\Camphor\Mocks;

class ComposingContainer
{
    /**
     * @var DataContainer
     */
    private $dataContainer;

    /**
     * @param DataContainer $dataContainer
     */
    public function __construct(DataContainer $dataContainer)
    {
        $this->dataContainer = $dataContainer;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->dataContainer->getValue();
    }
}
