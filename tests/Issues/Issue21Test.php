<?php

declare(strict_types=1);

namespace Tests\Issues;

use Nette\Application\Request;
use Nette\Application\UI\Presenter;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 * @see https://github.com/mrtnzlml/testbench/issues/21
 */
class Issue21Test extends \Tester\TestCase
{
    use \Testbench\TPresenter;
    use \Testbench\TCompiledContainer;

    public function testGetParametersPersistence(): void
    {
        $this->checkAction('Presenter:default', ['getparam' => 'getparamvalue']);

        /** @var Presenter $presenter */
        $presenter = $this->getPresenter();
        /** @var Request $appRequest */
        $appRequest = $presenter->getRequest();
        /** @var \Nette\Http\Request $httpRequest */
        $httpRequest = $this->getContainer()->getService('httpRequest');

        Assert::same('getparamvalue', $appRequest->getParameter('getparam'));
        Assert::same('getparamvalue', $presenter->getParameter('getparam'));
        Assert::same('getparamvalue', $httpRequest->getQuery('getparam'));
    }

    public function testPostParametersPersistence(): void
    {
        $this->checkSignal('Presenter:default', 'signal', ['id' => 1], ['postparam' => 'postparamvalue']);

        /** @var Presenter $presenter */
        $presenter = $this->getPresenter();
        /** @var Request $appRequest */
        $appRequest = $presenter->getRequest();
        $httpRequest = $this->getContainer()->getService('httpRequest');

        Assert::same('postparamvalue', $appRequest->getPost('postparam'));
        Assert::same('postparamvalue', $httpRequest->getPost('postparam'));

        Assert::same(1, (int) $appRequest->getParameter('id'));
        Assert::same(1, (int) $presenter->getParameter('id'));
        Assert::same(1, (int) $httpRequest->getQuery('id'));
    }

    public function testRedirectPersistentParameter(): void
    {
        $this->checkRedirect('Presenter:redirectRss', '/x/y/rss', ['persistentParameter' => 'Url-En.coded Value MixedCasě?!']);
        $this->checkRedirect('Presenter:redirect', '/x/y', ['persistentParameter' => 'Url-En.coded Value MixedCasě?!']);
    }
}

(new Issue21Test())->run();
