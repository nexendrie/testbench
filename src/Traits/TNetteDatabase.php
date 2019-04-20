<?php
declare(strict_types = 1);

namespace Testbench;

trait TNetteDatabase
{

	protected function getContext(): \Nette\Database\Context
	{
		$container = ContainerFactory::create(FALSE);
		/** @var Mocks\NetteDatabaseConnectionMock $connection */
		$connection = $container->getByType(\Nette\Database\Connection::class);
		if (!$connection instanceof Mocks\NetteDatabaseConnectionMock) {
			$serviceNames = $container->findByType(\Nette\Database\Connection::class);
			throw new \LogicException(sprintf(
				'The service %s should be instance of ' . Mocks\NetteDatabaseConnectionMock::class . ', to allow lazy schema initialization.',
				reset($serviceNames)
			));
		}
		/** @var \Nette\Database\Context $context */
		$context = $container->getByType(\Nette\Database\Context::class);
		return $context;
	}

}
