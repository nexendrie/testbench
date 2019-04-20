<?php
declare(strict_types = 1);

namespace Testbench;

class TestbenchExtension extends \Nette\DI\CompilerExtension
{

	private $defaults = [
		'dbname' => NULL,               // custom initial test database name (should not be needed)
		'dbprefix' => '_testbench_',    // database prefix for created tests databases
		'migrations' => FALSE,          // set TRUE if you want to use Doctrine migrations
		'shareDatabase' => FALSE,       // should Testbench always create new databases (FALSE) or use shared databases (TRUE)
		'sqls' => [],                   // sqls you want to import during new test database creation
		'url' => 'http://test.bench/',  // fake URL for HTTP request mock
	];

	public function loadConfiguration(): void
	{
		$builder = $this->compiler->getContainerBuilder();
		$builder->parameters[$this->name] = $this->validateConfig($this->defaults);

		$this->prepareDoctrine();
		$this->prepareNetteDatabase($builder);
		//TODO: $builder->addDefinition($this->prefix('applicationRequestMock'))->setClass('Testbench\ApplicationRequestMock');
	}

	public function beforeCompile(): void
	{
		$builder = $this->compiler->getContainerBuilder();

		if ($builder->hasDefinition($this->prefix('presenterMock'))) { //custom testbench.presenterMock implementation
			//workaround because of Application\UI\Presenter descendant (presenterMock needs to be reattached)
			$mockReplacement = $builder->getDefinition($this->prefix('presenterMock'))->getClass();
			$builder->removeDefinition($this->prefix('presenterMock'));
			$builder->addDefinition($this->prefix('presenterMock'))->setClass($mockReplacement);
		} else {
			$builder->addDefinition($this->prefix('presenterMock'))->setClass(\Testbench\Mocks\PresenterMock::class);
		}
	}

	/**
	 * 'wrapperClass' is not a service!
	 */
	private function prepareDoctrine(): void
	{
		$doctrineConnectionSectionKeys = ['dbname' => NULL, 'driver' => NULL, 'connection' => NULL];
		/** @var \Nette\DI\CompilerExtension $extension */
		foreach ($this->compiler->getExtensions(\Kdyby\Doctrine\DI\OrmExtension::class) as $extension) {
			if (array_intersect_key($extension->config, $doctrineConnectionSectionKeys)) {
				$extension->config['wrapperClass'] = \Testbench\Mocks\DoctrineConnectionMock::class;
			} else {
				foreach ($extension->config as $sectionName => $sectionConfig) {
					if (is_array($sectionConfig) && array_intersect_key($sectionConfig, $doctrineConnectionSectionKeys)) {
						$extension->config[$sectionName]['wrapperClass'] = \Testbench\Mocks\DoctrineConnectionMock::class;
					}
				}
			}
		}
	}

	private function prepareNetteDatabase(\Nette\DI\ContainerBuilder $builder): void
	{
		$ndbConnectionSectionKeys = ['dsn' => NULL, 'user' => NULL, 'password' => NULL];
		/** @var \Nette\DI\CompilerExtension $extension */
		foreach ($this->compiler->getExtensions(\Nette\Bridges\DatabaseDI\DatabaseExtension::class) as $extension) {
			if (array_intersect_key($extension->config, $ndbConnectionSectionKeys)) {
				$extensionConfig = $extension->config;
				$definitionName = $extension->name . '.default.connection';
				$builder->getDefinition($definitionName)
					->setClass(\Testbench\Mocks\NetteDatabaseConnectionMock::class, [
						$extensionConfig['dsn'],
						$extensionConfig['user'],
						$extensionConfig['password'],
						isset($extensionConfig['options']) ? ($extensionConfig['options'] + ['lazy' => TRUE]) : [],
					]);
			} else {
				foreach ($extension->config as $sectionName => $sectionConfig) {
					$definitionName = $extension->name . '.' . $sectionName . '.connection';
					$builder->getDefinition($definitionName)
						->setClass(\Testbench\Mocks\NetteDatabaseConnectionMock::class, [
							$sectionConfig['dsn'],
							$sectionConfig['user'],
							$sectionConfig['password'],
							isset($sectionConfig['options']) ? ($sectionConfig['options'] + ['lazy' => TRUE]) : [],
						]);
				}
			}
		}
	}

}
