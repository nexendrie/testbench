<?php

declare(strict_types=1);

namespace Tests\Traits;

use Tester\Assert;
use Testbench\Components\Component;

require getenv('BOOTSTRAP');

/**
 * @testCase
 */
class TComponentTest extends \Tester\TestCase
{
    use \Testbench\TComponent;

    public function testAttachToPresenter(): void
    {
        $control = new Component();
        Assert::exception(function () use ($control) {
            $control->lookup(\Nette\Application\IPresenter::class);
        }, \Nette\InvalidStateException::class, "Component '' is not attached to '" . \Nette\Application\IPresenter::class . "'.");
        $this->attachToPresenter($control);
        Assert::type(\Testbench\CustomPresenterMock::class, $control->lookup(\Nette\Application\IPresenter::class));
    }

    public function testRender(): void
    {
        $control = new Component();
        $this->checkRenderOutput($control, '<strong>OK</strong>');
        $this->checkRenderOutput($control, __DIR__ . '/Component.expected');
    }

    /**
     * @see vendor/nette/application/tests/Bridges.Latte/UIMacros.control.2.phpt
     */
    public function testRenderWithParametersNetteCompatibility(): void
    {
        $latte = new \Latte\Engine();
        $latte->setLoader(new \Latte\Loaders\StringLoader());
        \Nette\Bridges\ApplicationLatte\UIMacros::install($latte->getCompiler());
        $latte->addProvider('uiControl', new \Testbench\Components\ComponentWithParameters());

        Assert::same('["var1"]', $latte->renderToString('{control cwp var1}'));
        Assert::same('["var1",1,2]', $latte->renderToString('{control cwp var1, 1, 2}'));
        Assert::same('[{"var1":5,"0":1,"1":2}]', $latte->renderToString('{control cwp var1 => 5, 1, 2}'));
    }

    public function testRenderWithParameters(): void
    {
        $control = new \Testbench\Components\ComponentWithParameters();
        $this->checkRenderOutput($control, '[1]', [1]);
        $this->checkRenderOutput($control, '[1,"2"]', [1, '2']);
    }

    public function testRenderWithExplicitAttach(): void
    {
        $this->attachToPresenter($control = new Component());
        $this->checkRenderOutput($control, '<strong>OK</strong>');
        $this->checkRenderOutput($control, __DIR__ . '/Component.expected');
    }

    public function testMultipleAttaches(): void
    {
        $control = new Component();
        $this->attachToPresenter($control);
        Assert::type(\Testbench\CustomPresenterMock::class, $control->lookup(\Nette\Application\IPresenter::class));
        $this->attachToPresenter($control);
        Assert::type(\Testbench\CustomPresenterMock::class, $control->lookup(\Nette\Application\IPresenter::class));
        \Tester\Environment::$checkAssertions = false;
    }

    public function testMultipleAttachesDifferentComponents(): void
    {
        $this->attachToPresenter($control = new Component(), 'name_1');
        Assert::type(\Testbench\CustomPresenterMock::class, $control->lookup(\Nette\Application\IPresenter::class));
        $this->attachToPresenter($control = new Component(), 'name_2');
        Assert::type(\Testbench\CustomPresenterMock::class, $control->lookup(\Nette\Application\IPresenter::class));
        \Tester\Environment::$checkAssertions = false;
    }
}

(new TComponentTest())->run();
