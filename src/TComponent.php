<?php

declare(strict_types=1);

namespace Testbench;

use Nette\ComponentModel\IComponent;

trait TComponent
{
    private $testbench_presenterMock;

    protected function attachToPresenter(IComponent $component, string $name = null): void
    {
        if ($name === null) {
            if (!$name = $component->getName()) {
                $name = $component->getReflection()->getShortName();
                if (preg_match('~class@anonymous.*~', $name)) {
                    $name = md5($name);
                }
            }
        }
        if (!$this->testbench_presenterMock) {
            $container = ContainerFactory::create(false);
            $this->testbench_presenterMock = $container->getByType(\Testbench\Mocks\PresenterMock::class);
            $container->callInjects($this->testbench_presenterMock);
        }
        $this->testbench_presenterMock->onStartup[] = function (Mocks\PresenterMock $presenter) use ($component, $name) {
            try {
                $presenter->removeComponent($component);
            } catch (\Nette\InvalidArgumentException $exc) {
            }
            $presenter->addComponent($component, $name);
        };
        $this->testbench_presenterMock->run(new \Nette\Application\Request('Foo'));
    }

    protected function checkRenderOutput(IComponent $control, string $expected, array $renderParameters = [])
    {
        if (!$control->getParent()) {
            $this->attachToPresenter($control);
        }
        ob_start();
        $control->render(...$renderParameters);
        if (is_file($expected)) {
            \Tester\Assert::matchFile($expected, ob_get_clean());
        } else {
            \Tester\Assert::match($expected, ob_get_clean());
        }
    }
}
