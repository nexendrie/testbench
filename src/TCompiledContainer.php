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
}
