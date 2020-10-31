<?php

declare(strict_types=1);

namespace Tests\Issues;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 * @see https://github.com/mrtnzlml/testbench/issues/17
 */
class Issue17 extends \Tester\TestCase
{
    use \Testbench\TPresenter;

    /**
     * @dataProvider commentFormParameters
     */
    public function testCommentForm($params, $post, $shouldFail = true)
    {
        if ($shouldFail) {
            Assert::exception(function () use ($params, $post) {
                $this->check('Presenter:default', $params, $post);
            }, \Tester\AssertException::class, "field 'test' returned this error(s):\n  - This field is required.");
        } else {
            $this->check('Presenter:default', $params, $post);
        }
        $errors = $this->getPresenter()->getComponent('form1')->getErrors();
        if ($shouldFail) {
            Assert::same(['This field is required.'], $errors);
        } else {
            Assert::same([], $errors);
        }
    }

    /**
     * @dataProvider commentFormParametersBetter
     */
    public function testCommentFormBetter($post, $shouldFail = true)
    {
        if ($shouldFail) {
            Assert::exception(function () use ($post, $shouldFail) {
                $this->checkForm('Presenter:default', 'form1', $post, $shouldFail ? false : '/x/y');
            }, 'Tester\AssertException', "field 'test' returned this error(s):\n  - This field is required.");
            $errors = $this->getPresenter()->getComponent('form1')->getErrors();
            Assert::same(['This field is required.'], $errors);
        } else {
            $this->checkForm('Presenter:default', 'form1', $post, $shouldFail ? false : '/x/y');
            $errors = $this->getPresenter()->getComponent('form1')->getErrors();
            Assert::same([], $errors);
        }
    }

    public function commentFormParameters()
    {
        return [
            [['do' => 'form1-submit'], ['test' => null], true],
            [['do' => 'form1-submit'], ['test' => 'NOT NULL'], false],
        ];
    }

    public function commentFormParametersBetter()
    {
        return [
            [['test' => null], true],
            [['test' => 'NOT NULL'], false],
        ];
    }
}

(new Issue17())->run();
