<?php

declare(strict_types=1);

namespace Tests\Issues;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 * @see https://github.com/mrtnzlml/testbench/issues/22
 * @phpVersion 7
 */
class Issue22Test extends \Tester\TestCase
{
    use \Testbench\TComponent;

    public function testAnonymousComponentRender(): void
    {
        $control = new class extends \Nette\Application\UI\Control
        {
            public function render(): void
            {
                echo 'ok';
            }
        };
        $this->checkRenderOutput($control, 'ok');
    }
}

(new Issue22Test())->run();
