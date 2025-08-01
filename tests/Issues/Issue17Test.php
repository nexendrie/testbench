<?php

declare(strict_types=1);

namespace Tests\Issues;

use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 * @see https://github.com/mrtnzlml/testbench/issues/17
 */
class Issue17Test extends \Tester\TestCase
{
    use \Testbench\TPresenter;

    /**
     * @dataProvider commentFormParameters
     */
    public function testCommentForm(array $params, array $post, bool $shouldFail = true): void
    {
        if ($shouldFail) {
            Assert::exception(function () use ($params, $post) {
                $this->check('Presenter:default', $params, $post);
            }, \Tester\AssertException::class, "field 'test' returned this error(s):\n  - This field is required.");
        } else {
            $this->check('Presenter:default', $params, $post);
        }
        /** @var Presenter $presenter */
        $presenter = $this->getPresenter();
        /** @var Form $component */
        $component = $presenter->getComponent('form1');
        $errors = $component->getErrors();
        if ($shouldFail) {
            Assert::same(['This field is required.'], $errors);
        } else {
            Assert::same([], $errors);
        }
    }

    /**
     * @dataProvider commentFormParametersBetter
     */
    public function testCommentFormBetter(array $post, bool $shouldFail = true): void
    {
        if ($shouldFail) {
            Assert::exception(function () use ($post) {
                $this->checkForm('Presenter:default', 'form1', $post, false);
            }, 'Tester\AssertException', "field 'test' returned this error(s):\n  - This field is required.");
            /** @var Presenter $presenter */
            $presenter = $this->getPresenter();
            /** @var Form $component */
            $component = $presenter->getComponent('form1');
            $errors = $component->getErrors();
            Assert::same(['This field is required.'], $errors);
        } else {
            $this->checkForm('Presenter:default', 'form1', $post, '/x/y');
            /** @var Presenter $presenter */
            $presenter = $this->getPresenter();
            /** @var Form $component */
            $component = $presenter->getComponent('form1');
            $errors = $component->getErrors();
            Assert::same([], $errors);
        }
    }

    public function commentFormParameters(): array
    {
        return [
            [['do' => 'form1-submit'], ['test' => null], true],
            [['do' => 'form1-submit'], ['test' => 'NOT NULL'], false],
        ];
    }

    public function commentFormParametersBetter(): array
    {
        return [
            [['test' => null], true],
            [['test' => 'NOT NULL'], false],
        ];
    }
}

(new Issue17Test())->run();
