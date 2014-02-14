<?php

namespace EasyBib\Tests\Camphor;

use EasyBib\Camphor\CacheAspect;
use EasyBib\Camphor\CachingFilter;
use EasyBib\Camphor\MultipleRegistrationException;
use EasyBib\Camphor\NonexistentClassException;
use EasyBib\Camphor\NonexistentMethodException;
use EasyBib\Camphor\InvalidArgumentException;
use EasyBib\Camphor\PrivateMethodException;
use EasyBib\Tests\Camphor\Mocks\ComposingContainer;
use EasyBib\Tests\Camphor\Mocks\DataContainer;

class CacheAspectTest extends \PHPUnit_Framework_TestCase
{
    private static $cacheAspect;

    /**
     * We need to set this up only once, since it creates a new class
     * dynamically. We cannot use PHPUnit's process isolation option, because
     * PHPUnit then attempts to share state across processes using serialization,
     * and we run into 'serialization of Closure' errors
     */
    public static function setUpBeforeClass()
    {
        $cachingFilter = new CachingFilter();
        self::$cacheAspect = new CacheAspect($cachingFilter);
        self::$cacheAspect->register(ComposingContainer::class, ['getValue']);
    }

    public function setUp()
    {
        parent::setUp();

        self::$cacheAspect->reset();
    }

    /**
     * @return array
     */
    public function getValidArguments()
    {
        return [
            ['jim'],
            [123],
            [56.78],
            [['foo' => 'bar']],
        ];
    }

    /**
     * @return array
     */
    public function getInvalidArguments()
    {
        return [
            [curl_init()],
        ];
    }

    public function testRegister()
    {
        $cachingClassName = str_replace('ComposingContainer', 'CachingComposingContainer', ComposingContainer::class);
        $this->assertClassExists($cachingClassName);
    }

    public function testRegisterMultipleCalls()
    {
        $this->setExpectedException(MultipleRegistrationException::class);
        self::$cacheAspect->register(ComposingContainer::class, []);
    }

    public function testRegisterWithNonexistentClass()
    {
        $this->setExpectedException(NonexistentClassException::class);
        self::$cacheAspect->register('NoSuchClass', []);
    }

    public function testRegisterWithNonexistentMethods()
    {
        $this->setExpectedException(NonexistentMethodException::class);
        self::$cacheAspect->register(DataContainer::class, ['noSuchMethod']);
    }

    public function testRegisterOnPrivateMethod()
    {
        $this->setExpectedException(PrivateMethodException::class);
        self::$cacheAspect->register(DataContainer::class, ['doSomethingPrivate']);
    }

    public function testCachedMethodOnce()
    {
        $dataValue = 'ABC123';

        $dataContainer = new DataContainer($dataValue);
        $composingContainer = new \EasyBib\Tests\Camphor\Mocks\CachingComposingContainer($dataContainer);

        $this->assertEquals($dataValue, $composingContainer->getValue());
    }

    /**
     * @param mixed $arg
     * @dataProvider getValidArguments
     */
    public function testCachedMethod($arg)
    {
        $dataValue = 'ABC123';

        $mockDataContainer = $this->getMockBuilder(DataContainer::class)
            ->setConstructorArgs([$dataValue])
            ->getMock();

        $mockDataContainer->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($dataValue));

        $composingContainer = new \EasyBib\Tests\Camphor\Mocks\CachingComposingContainer($mockDataContainer);

        $directCall = $composingContainer->getValue($arg);
        $cachedCall = $composingContainer->getValue($arg);

        $this->assertEquals($dataValue, $directCall);
        $this->assertEquals($dataValue, $cachedCall);
    }

    public function testCachedMethodWithNewArg()
    {
        $dataValue = 'ABC123';

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
     * @dataProvider getInvalidArguments
     */
    public function testCachedMethodWithNonscalarArg($arg)
    {
        $dataContainer = new DataContainer('ABC123');
        $composingContainer = new \EasyBib\Tests\Camphor\Mocks\CachingComposingContainer($dataContainer);

        $this->setExpectedException(InvalidArgumentException::class);
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
