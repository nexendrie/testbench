<?php
declare(strict_types = 1);

class ComponentWithParameters extends \Nette\Application\UI\Control
{

	public function render($parameterOne, $parameterTwo = NULL): void
	{
		echo json_encode(func_get_args(), JSON_OBJECT_AS_ARRAY);
	}

	public function getComponent($name, $need = TRUE): self
	{
		return new self;
	}

}
