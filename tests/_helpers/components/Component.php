<?php

declare(strict_types=1);

/**
 * @property \Nette\Bridges\ApplicationLatte\Template $template
 */
class Component extends \Nette\Application\UI\Control
{
    public function render(): void
    {
        $this->template->render(__DIR__ . '/Component.latte');
    }
}
