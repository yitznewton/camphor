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

    /**
     * @return array
     */
    public function getValidData()
    {
        return [
            [[123], '123'],
            [[123, 456], '123|456'],
            [[123.45], '123.45'],
            [[['foo' => 'bar']], serialize(['foo' => 'bar'])],
        ];
    }

    /**
     * @dataProvider getValidData
     * @param array $args
     * @param $expectedKeySuffix
     */
    public function testGenerateWithValidArgs($args, $expectedKeySuffix)
    {
        $className = 'FooClass';
        $methodName = 'barMethod';

        $actualKey = $this->generator->generate($className, $methodName, $args);
        $expectedKey = sprintf('FooClass|barMethod|%s', $expectedKeySuffix);
        $this->assertEquals($expectedKey, $actualKey);
    }
}
