<?php

declare(strict_types=1);

/**
 * @property \Nette\Bridges\ApplicationLatte\Template $template
 */
class ComponentWithParameters extends \Nette\Application\UI\Control
{

  /**
   * @param mixed $parameterOne
   * @param mixed $parameterTwo
   */
    public function render($parameterOne, $parameterTwo = null): void
    {
        echo json_encode(func_get_args(), JSON_OBJECT_AS_ARRAY);
    }

    public function getComponent($name, $need = true): self
    {
        return new self();
    }
}
