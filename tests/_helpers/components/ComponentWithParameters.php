<?php

declare(strict_types=1);

/**
 * @property \Nette\Bridges\ApplicationLatte\Template $template
 */
class ComponentWithParameters extends \Nette\Application\UI\Control
{

    public function render(string $parameterOne, int $parameterTwo = null): void
    {
        echo json_encode(func_get_args(), JSON_OBJECT_AS_ARRAY);
    }

    public function getComponent($name, $need = true): self
    {
        return new self();
    }
}
