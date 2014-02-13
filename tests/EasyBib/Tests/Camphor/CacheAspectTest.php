<?php

namespace EasyBib\Tests\Camphor;

use Doctrine\Common\Cache\ArrayCache;
use EasyBib\Camphor\CacheAspect;
use EasyBib\Camphor\MultipleRegistrationException;
use EasyBib\Camphor\NonexistentClassException;
use EasyBib\Camphor\NonexistentMethodException;
use EasyBib\Camphor\NonscalarArgumentException;
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

    /**
     * @return array
     */
    public function getNonscalarArguments()
    {
        return [
            [[]],
            [new \stdClass()],
            // PHPUnit doesn't seem to like passing resources via data providers
            // [curl_init()],
        ];
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

    public function testCachedMethodOnce()
    {
        $dataValue = 'ABC123';

        $this->cacheAspect->register(ComposingContainer::class, ['getValue']);

        $dataContainer = new DataContainer($dataValue);
        $composingContainer = new \EasyBib\Tests\Camphor\Mocks\CachingComposingContainer($dataContainer);

        $this->assertEquals($dataValue, $composingContainer->getValue());
    }

    public function testCachedMethod()
    {
        $dataValue = 'ABC123';

        $this->cacheAspect->register(ComposingContainer::class, ['getValue']);

        $mockDataContainer = $this->getMockBuilder(DataContainer::class)
            ->setConstructorArgs([$dataValue])
            ->getMock();

        $mockDataContainer->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($dataValue));

        $composingContainer = new \EasyBib\Tests\Camphor\Mocks\CachingComposingContainer($mockDataContainer);

        $directCall = $composingContainer->getValue();
        $cachedCall = $composingContainer->getValue();

        $this->assertEquals($dataValue, $directCall);
        $this->assertEquals($dataValue, $cachedCall);
    }

    public function testCachedMethodWithNewArg()
    {
        $dataValue = 'ABC123';

        $this->cacheAspect->register(ComposingContainer::class, ['getValue']);

        $mockDataContainer = $this->getMockBuilder(DataContainer::class)
            ->setConstructorArgs([$dataValue])
            ->getMock();

        $mockDataContainer->expects($this->at(0))
            ->method('getValue')
            ->will($this->returnValue($dataValue));

        $mockDataContainer->expects($this->at(1))
            ->method('getValue')
            ->will($this->returnValue($dataValue));

        $composingContainer = new \EasyBib\Tests\Camphor\Mocks\CachingComposingContainer($mockDataContainer);

        $directCall = $composingContainer->getValue();
        $cachedCall = $composingContainer->getValue('bob');
    }

    /**
     * @param mixed $arg
     * @dataProvider getNonscalarArguments
     */
    public function testCachedMethodWithNonscalarArg($arg)
    {
        $this->cacheAspect->register(ComposingContainer::class, ['getValue']);

        $dataContainer = new DataContainer('ABC123');
        $composingContainer = new \EasyBib\Tests\Camphor\Mocks\CachingComposingContainer($dataContainer);

        $this->setExpectedException(NonscalarArgumentException::class);
        $composingContainer->getValue($arg);
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
