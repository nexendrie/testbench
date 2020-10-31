<?php

declare(strict_types=1);

namespace Testbench\Mocks;

/**
 * @deprecated
 */
class ApplicationRequestMock extends \Nette\Application\Request
{

  /**
   * @param string|null $name
   * @param string|null $method
   */
    public function __construct($name = null, $method = null, array $params = [], array $post = [], array $files = [], array $flags = [])
    {
        $name = $name ?: 'Foo'; //It's going to be terminated anyway (see: \PresenterMock::afterRender)
        parent::__construct($name, $method, $params, $post, $files, $flags);
    }
}
