<?php

declare(strict_types=1);

namespace Testbench\Mocks;

/**
 * @method onStartup(self $presenter)
 */
class PresenterMock extends \Nette\Application\UI\Presenter
{

    /** @var callable[] */
    public $onStartup = [];

    public function run(\Nette\Application\Request $request): \Nette\Application\IResponse
    {
        $this->autoCanonicalize = false;
        return parent::run($request);
    }

  /**
   * @throws \Nette\Application\AbortException
   */
    public function startup(): void
    {
        if ($this->getParameter('__terminate') === true) {
            $this->terminate();
        }
        parent::startup();
        $this->onStartup($this);
    }

  /**
   * @throws \Nette\Application\AbortException
   */
    public function afterRender(): void
    {
        $this->sendPayload();
    }

    public function isAjax(): bool
    {
        return false;
    }

  /**
   * @param string $destination
   * @param array|mixed $args
   */
    public function link($destination, $args = []): string
    {
        if (!is_array($args)) {
            $args = array_slice(func_get_args(), 1);
        }
        $params = urldecode(http_build_query($args, '', ', '));
        $params = $params ? "($params)" : '';
        return "plink|$destination$params";
    }
}
