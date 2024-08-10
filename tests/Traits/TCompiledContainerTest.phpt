<?php

declare(strict_types=1);

namespace Tests\Traits;

use Tester\Assert;

require getenv('BOOTSTRAP');

/**
 * @testCase
 */
class TCompiledContainerTest extends \Tester\TestCase
{
    use \Testbench\TCompiledContainer;

    public function testGetContainer()
    {
        Assert::type(\Nette\DI\Container::class, $container = $this->getContainer());
        Assert::same($container, $this->getContainer());
    }

    public function testGetService()
    {
        Assert::type(\Nette\Application\Application::class, $this->getService(\Nette\Application\Application::class));
    }

    public function testRefreshContainer()
    {
        Assert::type(\Nette\DI\Container::class, $container = $this->getContainer());
        Assert::same($container, $this->getContainer());
        $refreshedContainer = $this->refreshContainer();
        Assert::type(\Nette\DI\Container::class, $refreshedContainer);
        Assert::notSame($container, $refreshedContainer);
    }

    public function testRefreshContainerWithConfig()
    {
        $container = $this->getContainer();
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $errorType = E_WARNING;
            $errorMessage = 'Undefined array key "test"';
        } else {
            $errorType = E_NOTICE;
            $errorMessage = 'Undefined index: test';
        }
        Assert::error(function () use ($container) {
            $container->parameters['test'];
        }, $errorType, $errorMessage);

        $refreshedContainer = $this->refreshContainer([
            'extensions' => ['test' => \Testbench\FakeExtension::class],
            'services' => ['test' => 'Testbench\FakeExtension'],
            'test' => ['xxx' => ['yyy']],
        ]);
        Assert::same(['xxx' => ['yyy']], $refreshedContainer->parameters['test']);
        Assert::type(\Testbench\FakeExtension::class, $extension = $refreshedContainer->getService('test'));
        Assert::true($extension::$tested);

        Assert::notSame($container, $refreshedContainer);
    }
}

(new TCompiledContainerTest())->run();
