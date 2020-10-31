<?php

declare(strict_types=1);

namespace Testbench;

trait TCompiledContainer
{

    protected function getContainer(): \Nette\DI\Container
    {
        return ContainerFactory::create(false);
    }

    protected function getService($class): ?object
    {
        return $this->getContainer()->getByType($class);
    }

    protected function refreshContainer($config = []): \Nette\DI\Container
    {
        return ContainerFactory::create(true, $config);
    }

    protected function changeRunLevel(int $testSpeed = \Testbench::FINE): void
    {
        if ((int) getenv('RUNLEVEL') < $testSpeed) {
            \Tester\Environment::skip(
                "Required runlevel '$testSpeed' but current runlevel is '" . (int) getenv('RUNLEVEL') . "' (higher runlevel means slower tests)\nYou can run this test with environment variable: 'RUNLEVEL=$testSpeed vendor/bin/run-tests ...'\n"
            );
        }
    }

    protected function markTestAsSlow(bool $really = true): void
    {
        $this->changeRunLevel($really ? \Testbench::FINE : \Testbench::QUICK);
    }

    protected function markTestAsVerySlow(bool $really = true): void
    {
        $this->changeRunLevel($really ? \Testbench::SLOW : \Testbench::QUICK);
    }
}
