<?php

declare(strict_types=1);

namespace Tests\Traits;

use Nette\Application\UI\Presenter;
use Tester\Assert;
use Tester\Dumper;

require getenv('BOOTSTRAP');

/**
 * @testCase
 */
class TPresenterTest extends \Testbench\CustomPresenterTestCase
{
    public function testClassicRender(): void
    {
        $this->checkAction('Presenter:default');

        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $errorType = E_WARNING;
            $errorMessage = 'Undefined variable $doesnexist';
        } else {
            $errorType = E_NOTICE;
            $errorMessage = 'Undefined variable: doesnexist';
        }
        Assert::error(function () {
            $this->checkAction('Presenter:variabledoesntexist');
        }, $errorType, $errorMessage);
    }

    public function testClassicRenderShort(): void
    {
        $this->checkAction('Presenter:');
    }

    public function testClassicRenderFqn(): void
    {
        $this->checkAction(':Presenter:default');
    }

    public function testRenderBrokenLink(): void
    {
        $this->checkAction('Presenter:brokenLink'); //FIXME: should fail (?)
    }

    public function test404Render(): void
    {
        Assert::exception(function () {
            $this->checkAction('Presenter:404');
        }, \Nette\Application\BadRequestException::class);
        Assert::same(404, $this->getReturnCode());
    }

    public function test500Render(): void
    {
        Assert::exception(function () {
            $this->checkAction('Presenter:fail');
        }, \Nette\Application\BadRequestException::class);
        Assert::same(500, $this->getReturnCode());
    }

    public function testRenderException(): void
    {
        Assert::exception(function () {
            $this->checkAction('Presenter:exception');
        }, \Latte\CompileException::class);
        Assert::type(\Latte\CompileException::class, $this->getException());
    }

    public function testRedirect(): void
    {
        $this->checkRedirect('Presenter:redirect', '/x/y');
    }

    public function testRedirectRss(): void
    {
        $this->checkRedirect('Presenter:redirectRss', '/x/y/rss');
        $this->checkRedirect('Presenter:redirectRss', '/.*');
        $this->checkRedirect('Presenter:redirectRss', '/(x|y)/(x|y)/.?s{2}');
    }

    public function testRedirectRssFailedUrl(): void
    {
        $path = Dumper::color('yellow') . Dumper::toLine('/x/y/rs') . Dumper::color('white');
        $url = Dumper::color('yellow') . Dumper::toLine('http://test.bench/x/y/rss') . Dumper::color('white');
        Assert::error(function () {
            $this->checkRedirect('Presenter:redirectRss', '/x/y/rs', [
                'flashMessage' => false,
            ]);
        }, \Tester\AssertException::class, str_repeat(' ', 4) . "path $path doesn't match\n$url\nafter redirect");
    }

    public function testJsonOutput(): void
    {
        $this->checkJsonScheme('Presenter:json', [
            'string' => [
                1234 => [],
            ],
        ]);
        Assert::exception(function () {
            $this->checkJsonScheme('Presenter:json', ['string']);
        }, \Tester\AssertException::class);
    }

    public function testRss(): void
    {
        $this->checkRss('Presenter:rss');
    }

    public function testSitemap(): void
    {
        $this->checkSitemap('Presenter:sitemap');
    }

    public function testUserLogIn(): void
    {
        $user = $this->logIn();
        Assert::true($user->isLoggedIn());
    }

    public function testUserLogInWithId(): void
    {
        $user = $this->logIn(1);
        Assert::true($user->isLoggedIn());
        Assert::same(1, $user->identity->id);
    }

    public function testUserLogInWithIdRole(): void
    {
        $user = $this->logIn(1, ['admin']);
        Assert::true($user->isLoggedIn());
        Assert::same(1, $user->identity->id);
        Assert::true($user->isInRole('admin'));
    }

    public function testUserLogInWithIdRoles(): void
    {
        $user = $this->logIn(1, ['test1', 'test2']);
        Assert::true($user->isLoggedIn());
        Assert::same(1, $user->identity->id);
        Assert::true($user->isInRole('test1'));
        Assert::true($user->isInRole('test2'));
        Assert::false($user->isInRole('admin'));
    }

    public function testUserLogInWithIdentity(): void
    {
        $user = $this->logIn($identity = new \Nette\Security\Identity(123, ['Role_1', 'Role_2']), ['Role_3']);

        Assert::true($user->isLoggedIn());
        Assert::same($identity, $user->getIdentity());
        Assert::true($user->isInRole('Role_1'));
        Assert::true($user->isInRole('Role_2'));
        Assert::false($user->isInRole('Role_3'));
        Assert::same(['Role_1', 'Role_2'], $user->getRoles());
    }

    public function testUserLogOut(): void
    {
        $user = $this->logOut();
        Assert::false($user->isLoggedIn());
    }

    public function testPresenterInstance(): void
    {
        Assert::null($this->getPresenter()); //presenter is not open yet
        $this->checkAction('Presenter:default');
        Assert::type(\Nette\Application\UI\Presenter::class, $this->getPresenter()); //presenter is not open yet
    }

    public function testForm(): void
    {
        $this->checkForm('Presenter:default', 'form1', [
            'test' => 'test',
        ], '/x/y');

        Assert::exception(function () {
            $this->checkForm('Presenter:default', 'form1', []);
        }, \Tester\AssertException::class, "field 'test' returned this error(s):\n  - This field is required.");

        Assert::exception(function () {
            $this->checkForm('Presenter:default', 'form1', [
                'test' => 'test',
                'error' => 'FORM ERROR',
            ]);
        }, \Tester\AssertException::class, "Intended error: FORM ERROR");

        Assert::exception(function () {
            $this->checkForm('Presenter:default', 'form1', [
                'test' => 'test',
            ]); //missing path
        }, \Tester\AssertException::class);
    }

    public function testFormDifferentDestination(): void
    {
        $this->checkForm('Presenter:default', 'form2', [
            'test' => 'test',
        ], '/x/y/json');
    }

    public function testFormWithoutRedirect(): void
    {
        $this->checkForm('Presenter:default', 'form3', [
            'test' => 'test',
        ], false); //do not check redirect
    }

    public function testAjaxForm(): void
    {
        $this->checkForm('Presenter:default', 'ajaxForm', [
            'test' => 'test',
        ], '/x/y/json');

        $this->checkAjaxForm('Presenter:default', 'ajaxForm', [
            'test' => 'test',
        ]);

        $this->checkAjaxForm('Presenter:default', 'ajaxForm', [
            'test' => 'test',
        ], '/x/y/json');
    }

    public function testCsrfForm(): void
    {
        $this->checkForm('Presenter:default', 'csrfForm', [
            'test' => 'test',
        ], '/x/y');
    }

    public function testSignal(): void
    {
        $this->checkSignal('Presenter:default', 'signal');
    }

    public function testAjaxSignal(): void
    {
        /** @var \Nette\Application\Responses\JsonResponse $response */
        $response = $this->checkAjaxSignal('Presenter:default', 'ajaxSignal');
        Assert::same(['ok'], $response->getPayload());
    }

    public function testFormEnhanced(): void
    {
        $this->checkForm('Presenter:default', 'form1', [
            'a' => 'b',
            'test' => [
                \Nette\Forms\Form::REQUIRED => true,
                'value',
            ],
        ], '/x/y');
        /** @var Presenter $presenter */
        $presenter = $this->getPresenter();
        /** @var \ArrayIterator<int|string, mixed> $iterator */
        $iterator = $presenter->getFlashSession()->getIterator();
        Assert::same(
            '{"test":"value","error":""}',
            $iterator->getArrayCopy()['flash'][0]->message
        );
        Assert::exception(function () {
            $this->checkForm('Presenter:default', 'form4', [
                'a' => 'b',
                'test' => [
                    'value',
                    \Nette\Forms\Form::REQUIRED => true,
                ],
            ], '/x/y');
        }, \Tester\AssertException::class, "field 'test' should be defined as required, but it's not");
    }

    public function testUserLoggedIn(): void
    {
        Assert::false($this->isUserLoggedIn());
        $this->logIn();
        Assert::true($this->isUserLoggedIn());
        $this->logOut();
        Assert::false($this->isUserLoggedIn());
    }
}

(new TPresenterTest())->run();
