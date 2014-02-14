<?php

namespace EasyBib\Tests\Camphor;

use EasyBib\Camphor\ArgumentValidator;
use EasyBib\Camphor\InvalidArgumentException;

class ArgumentValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ArgumentValidator
     */
    private $argumentValidator;

    public function setUp()
    {
        $this->argumentValidator = new ArgumentValidator();
    }

    /**
     * @return array
     */
    public function getValidData()
    {
        return [
            ['foo'],
            [123],
            [123.45],
            [['foo' => 'bar']],
            [new \stdClass()],
            [['foo' => new \stdClass()]],
            [true],
        ];
    }

    /**
     * @return array
     */
    public function getInvalidData()
    {
        return [
            [curl_init()],
        ];
    }

    /**
     * @dataProvider getValidData
     * @param mixed $argument
     */
    public function testValidateWithValid($argument)
    {
        $this->argumentValidator->validate($argument);
    }

    /**
     * @dataProvider getInvalidData
     * @param mixed $argument
     */
    public function testValidateWithInvalid($argument)
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $this->argumentValidator->validate($argument);
    }
}
