<?php
namespace Foo\Bar\Tests\Base;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;

/**
 * TestListener
 * 
 * @example
 * <phpunit>
 *   <listeners>
     *   <listener class="Mosaiq\MqFormQueue\Tests\Base\TestListener" file="Base/TestListener.php"></listener>
 *   </listeners>
 * </phpunit>
 */
class TestListener implements \PHPUnit\Framework\TestListener
{
    public function startTestSuite(TestSuite $suite) : void
    {
        if($suite->getName() === 'functional')
        {
        }
    }

    public function addError(Test $test, \Throwable $t, float $time) : void
    {}

    public function addWarning(Test $test, Warning $e, float $time) : void
    {}

    public function addFailure(Test $test, AssertionFailedError $e, float $time) : void
    {}

    public function addIncompleteTest(Test $test, \Throwable $t, float $time) : void
    {}

    public function addRiskyTest(Test $test, \Throwable $t, float $time) : void
    {}

    public function addSkippedTest(Test $test, \Throwable $t, float $time) : void
    {}

    public function endTestSuite(TestSuite $suite) : void
    {}

    public function startTest(Test $test) : void
    {}

    public function endTest(Test $test, float $time) : void
    {}
}
