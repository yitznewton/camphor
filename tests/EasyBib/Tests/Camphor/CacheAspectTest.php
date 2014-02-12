<?php

namespace EasyBib\Tests\Camphor;

use Doctrine\Common\Cache\ArrayCache;
use EasyBib\Camphor\CacheAspect;
use EasyBib\Tests\Camphor\Mocks\ComposingContainer;
use EasyBib\Tests\Camphor\Mocks\DataContainer;

class CacheAspectTest extends \PHPUnit_Framework_TestCase
{
    private $cacheAspect;

    public function setUp()
    {
        parent::setUp();

        $this->cacheAspect = new CacheAspect(new ArrayCache());
    }

    public function testRegister()
    {
        $this->markTestIncomplete();

        $dataValue = 'ABC';

        $this->cacheAspect->register('FooClass', ['barMethod']);

        $dataContainer = $this->getMockBuilder(DataContainer::class)
            ->setConstructorArgs([$dataValue])
            ->getMock();

        $dataContainer->expects($this->once())
            ->method('getValue');

        $foo = new ComposingContainer($dataContainer);

        $directCall = $foo->getValue();
        $cachedCall = $foo->getValue();

        $this->assertEquals($dataValue, $directCall);
        $this->assertEquals($dataValue, $cachedCall);
    }
}
