<?php

declare(strict_types=1);

namespace Tests\Traits;

require getenv('BOOTSTRAP');

/**
 * @testCase
 */
class PresenterModuleTest extends \Tester\TestCase
{
    use \Testbench\TPresenter;

    public function testClassicRender1(): void
    {
        $this->checkAction('Module:Presenter:');
    }

    public function testClassicRender2(): void
    {
        $this->checkAction('Module:Presenter:default');
    }

    public function testClassicRender3(): void
    {
        $this->checkAction(':Module:Presenter:default');
    }

    public function testMultipleSame(): void
    {
        $this->checkAction('Module:Presenter:');
        $this->checkAction('Module:Presenter:default');
        $this->checkAction(':Module:Presenter:default');
    }
}

(new PresenterModuleTest())->run();
