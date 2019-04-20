<?php
declare(strict_types = 1);

class ScaffoldPresenter extends Nette\Application\UI\Presenter
{

	const TEST = 'xyz';

	public function renderDefault($variable, $optional = 'optionalValue', $nullable = NULL, $const = ScaffoldPresenter::TEST): void
	{
		$this->template->variable = $variable;
	}

}
