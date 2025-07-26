<?php

declare(strict_types=1);

namespace Tests\Traits;

require getenv('BOOTSTRAP');

/**
 * @testCase
 */
class MultipleTraitsTest extends \Tester\TestCase
{
    use \Testbench\TCompiledContainer;
    use \Testbench\TComponent;
    use \Testbench\TDoctrine;
    use \Testbench\TNetteDatabase;
    use \Testbench\TPresenter;

    public function testShutUp(): void
    {
        \Tester\Environment::$checkAssertions = false;
    }
}

(new MultipleTraitsTest())->run();
