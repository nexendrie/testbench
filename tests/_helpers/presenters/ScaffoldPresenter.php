<?php

declare(strict_types=1);

class ScaffoldPresenter extends Nette\Application\UI\Presenter
{

    public const TEST = 'xyz';

  /**
   * @param mixed $variable
   * @param mixed $optional
   * @param mixed $nullable
   * @param mixed $const
   */
    public function renderDefault($variable, $optional = 'optionalValue', $nullable = null, $const = self::TEST): void
    {
        $this->template->variable = $variable;
    }
}
