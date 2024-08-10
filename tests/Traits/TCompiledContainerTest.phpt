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
        Assert::error(function () use ($container) {
            $container->parameters['test'];
        }, 'E_NOTICE', 'Undefined index: test');

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
