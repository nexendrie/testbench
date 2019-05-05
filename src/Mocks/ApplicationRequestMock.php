<?php
declare(strict_types = 1);

namespace Testbench\Mocks;

/**
 * @deprecated
 */
class ApplicationRequestMock extends \Nette\Application\Request
{

	public function __construct($name = NULL, $method = NULL, array $params = [], array $post = [], array $files = [], array $flags = [])
	{
		$name = $name ?: 'Foo'; //It's going to be terminated anyway (see: \PresenterMock::afterRender)
		parent::__construct($name, $method, $params, $post, $files, $flags);
	}

}
