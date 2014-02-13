<?php

namespace EasyBib\Tests\Camphor;

use EasyBib\Camphor\CacheKeyGenerator;

class CacheKeyGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CacheKeyGenerator
     */
    private $generator;

    public function setUp()
    {
        parent::setUp();

        $this->generator = new CacheKeyGenerator();
    }

    public function testGenerateWithStringArgs()
    {
        $className = 'FooClass';
        $methodName = 'barMethod';
        $arg0 = 'some_value';
        $args = [$arg0];

        $key = $this->generator->generate($className, $methodName, $args);
        $this->assertEquals('FooClass.barMethod.some_value', $key);
    }
}
