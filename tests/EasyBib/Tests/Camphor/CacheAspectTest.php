<?php

namespace EasyBib\Tests\Camphor;

use Doctrine\Common\Cache\ArrayCache;
use EasyBib\Camphor\CacheAspect;
use EasyBib\Camphor\MultipleRegistrationException;
use EasyBib\Camphor\NonexistentMethodException;
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
        $this->markTestIncomplete();
        // $this->setExpectedException(NonexistentClassException::class);
        // $this->cacheAspect->register('NoSuchClass', []);
    }

    public function testRegisterWithNonexistentMethods()
    {
        $this->setExpectedException(NonexistentMethodException::class);
        $this->cacheAspect->register(DataContainer::class, ['noSuchMethod']);
    }

    public function testCachedMethod()
    {
        $this->markTestIncomplete();
        $dataValue = 'ABC123';

        $this->cacheAspect->register(DataContainer::class, ['getValue']);

        $dataContainer = new \EasyBib\Tests\Camphor\Mocks\CachingDataContainer($dataValue);

        $foo = new ComposingContainer($dataContainer);

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
