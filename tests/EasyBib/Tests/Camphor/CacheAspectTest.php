<?php

namespace EasyBib\Tests\Camphor;

use Doctrine\Common\Cache\ArrayCache;
use EasyBib\Camphor\CacheAspect;
use EasyBib\Camphor\MultipleRegistrationException;
use EasyBib\Camphor\NonexistentClassException;
use EasyBib\Camphor\NonexistentMethodException;
use EasyBib\Camphor\PrivateMethodException;
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
        $cachingClassName = str_replace('DataContainer', 'CachingDataContainer', DataContainer::class);

        $this->cacheAspect->register(DataContainer::class, ['getValue']);

        $this->assertClassExists($cachingClassName);
    }

    public function testRegisterMultipleCalls()
    {
        $this->setExpectedException(MultipleRegistrationException::class);
        $this->cacheAspect->register(DataContainer::class, ['getValue']);
        $this->cacheAspect->register(DataContainer::class, ['getValue']);
    }

    public function testRegisterWithNonexistentClass()
    {
        $this->setExpectedException(NonexistentClassException::class);
        $this->cacheAspect->register('NoSuchClass', []);
    }

    public function testRegisterWithNonexistentMethods()
    {
        $this->setExpectedException(NonexistentMethodException::class);
        $this->cacheAspect->register(DataContainer::class, ['noSuchMethod']);
    }

    public function testRegisterOnPrivateMethod()
    {
        $this->setExpectedException(PrivateMethodException::class);
        $this->cacheAspect->register(DataContainer::class, ['doSomethingPrivate']);
    }

    public function testCachedMethod()
    {
        $this->markTestIncomplete();

        $dataValue = 'ABC123';

        $this->cacheAspect->register(DataContainer::class, ['getValue']);

        $mockDataContainer = $this->getMockBuilder('\EasyBib\Tests\Camphor\Mocks\CachingDataContainer')
            ->setConstructorArgs([$dataValue])
            ->getMock();

        $mockDataContainer->expects($this->once())
            ->method('getValue');

        $foo = new ComposingContainer($mockDataContainer);

        $directCall = $foo->getValue();
        $cachedCall = $foo->getValue();

        $this->assertEquals($dataValue, $directCall);
        $this->assertEquals($dataValue, $cachedCall);
    }

    /**
     * @param string $className
     */
    protected function assertClassExists($className)
    {
        if (!class_exists($className)) {
            $this->fail(sprintf('Expected class "%s" does not exist.', $className));
        }
    }
}
