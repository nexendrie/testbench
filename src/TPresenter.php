<?php

declare(strict_types=1);

namespace Testbench;

use Exception;
use Nette\Application\IPresenterFactory;
use Nette\Application\UI\Presenter;
use Tester\Assert;
use Tester\Dumper;

trait TPresenter
{

    private ?Presenter $testbench_presenter = null;

    private int $testbench_httpCode;

    private ?Exception $testbench_exception;

    private bool $testbench_ajaxMode = false;

    /**
     * @param string $destination fully qualified presenter name (module:module:presenter)
     * @param array $params provided to the presenter usually via URL
     * @param array $post provided to the presenter via POST
     *
     * @throws Exception
     */
    protected function check(string $destination, array $params = [], array $post = []): \Nette\Application\IResponse
    {
        $destination = ltrim($destination, ':');
        $pos = (int) strrpos($destination, ':');
        $presenter = substr($destination, 0, $pos);
        $action = substr($destination, $pos + 1) ?: 'default';

        $container = ContainerFactory::create(false);
        $container->removeService('httpRequest');
        $headers = $this->testbench_ajaxMode ? ['X-Requested-With' => 'XMLHttpRequest'] : [];
        $url = new \Nette\Http\UrlScript($container->parameters['testbench']['url']);
        $container->addService('httpRequest', new Mocks\HttpRequestMock($url, $params, $post, [], [], $headers));
        /** @var IPresenterFactory $presenterFactory */
        $presenterFactory = $container->getByType(IPresenterFactory::class);
        /** @var Presenter $presenter */
        $presenter = $presenterFactory->createPresenter($presenter);
        $this->testbench_presenter = $presenter;
        $this->testbench_presenter->autoCanonicalize = false;
        $this->testbench_presenter->invalidLinkMode = Presenter::INVALID_LINK_EXCEPTION;

        $postCopy = $post;
        if (isset($params['do'])) {
            foreach ($post as $key => $field) {
                if (is_array($field) && array_key_exists(\Nette\Forms\Form::REQUIRED, $field)) {
                    $post[$key] = $field[0];
                }
            }
        }

        /** @var \Kdyby\FakeSession\Session $session */
        $session = $this->testbench_presenter->getSession();
        $session->setFakeId('testbench.fakeId');
        $session->getSection('Nette\Forms\Controls\CsrfProtection')->token = 'testbench.fakeToken';
        $post = $post + ['_token_' => 'goVdCQ1jk0UQuVArz15RzkW6vpDU9YqTRILjE=']; //CSRF magic! ¯\_(ツ)_/¯

        $request = new \Nette\Application\Request(
            $presenter,
            $post ? 'POST' : 'GET',
            ['action' => $action] + $params,
            $post
        );
        try {
            $this->testbench_httpCode = 200;
            $this->testbench_exception = null;
            $response = $this->testbench_presenter->run($request);

            if (isset($params['do'])) {
                if (preg_match('~(.+)-submit$~', $params['do'], $matches)) {
                    /** @var \Nette\Application\UI\Form $form */
                    $form = $this->testbench_presenter->getComponent($matches[1]);
                    foreach ($form->getControls() as $control) {
                        if (array_key_exists($control->getName(), $postCopy)) {
                            $subvalues = $postCopy[$control->getName()];
                            $rq = \Nette\Forms\Form::REQUIRED;
                            if (is_array($subvalues) && array_key_exists($rq, $subvalues) && $subvalues[$rq]) {
                                if ($control->isRequired() !== true) {
                                    Assert::fail("field '{$control->name}' should be defined as required, but it's not");
                                }
                            }
                        }
                        if ($control->hasErrors()) {
                            $errors = '';
                            $counter = 1;
                            foreach ($control->getErrors() as $error) {
                                $errors .= "  - $error\n";
                                $counter++;
                            }
                            Assert::fail("field '{$control->name}' returned this error(s):\n$errors");
                        }
                    }
                    foreach ($form->getErrors() as $error) {
                        Assert::fail($error);
                    }
                }
            }

            return $response;
        } catch (Exception $exc) {
            $this->testbench_exception = $exc;
            $this->testbench_httpCode = $exc->getCode();
            throw $exc;
        }
    }

    /**
     * @param string $destination fully qualified presenter name (module:module:presenter)
     * @param array $params provided to the presenter usually via URL
     * @param array $post provided to the presenter via POST
     *
     * @throws Exception
     */
    protected function checkAction(string $destination, array $params = [], array $post = []): \Nette\Application\Responses\TextResponse
    {
        /** @var \Nette\Application\Responses\TextResponse $response */
        $response = $this->check($destination, $params, $post);
        if (!$this->testbench_exception) {
            Assert::same(200, $this->getReturnCode());
            Assert::type('Nette\Application\Responses\TextResponse', $response);
            Assert::type('Nette\Application\UI\ITemplate', $response->getSource());

            $html = (string) $response->getSource();
            //DOMDocument doesn't handle HTML tags inside of script tags very well
      /** @var string $html */
            $html = preg_replace('~<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>~', '', $html); //http://stackoverflow.com/a/6660315/3135248
            $dom = @\Tester\DomQuery::fromHtml($html);
            Assert::true($dom->has('html'), "missing 'html' tag");
            Assert::true($dom->has('title'), "missing 'title' tag");
            Assert::true($dom->has('body'), "missing 'body' tag");
        }
        return $response;
    }

    protected function checkSignal(string $destination, string $signal, array $params = [], array $post = []): \Nette\Application\IResponse
    {
        return $this->checkRedirect($destination, false, [
                'do' => $signal,
            ] + $params, $post);
    }

  /**
   * @param string $destination
   * @param string $signal
   * @param array $params
   * @param array $post
   * @return \Nette\Application\IResponse
   * @throws Exception
   */
    protected function checkAjaxSignal($destination, $signal, $params = [], $post = [])
    {
        $this->testbench_ajaxMode = true;
        $response = $this->check($destination, [
                'do' => $signal,
            ] + $params, $post);
        /** @var Presenter $presenter */
        $presenter = $this->testbench_presenter;
        Assert::true($presenter->isAjax());
        if (!$this->testbench_exception) {
            Assert::same(200, $this->getReturnCode());
            Assert::type(\Nette\Application\Responses\JsonResponse::class, $response);
        }
        $this->testbench_ajaxMode = false;
        return $response;
    }

    /**
     * @param string $destination fully qualified presenter name (module:module:presenter)
     * @param string|bool $path
     * @param array $params provided to the presenter usually via URL
     * @param array $post provided to the presenter via POST
     *
     * @return \Nette\Application\Responses\RedirectResponse
     * @throws Exception
     */
    protected function checkRedirect(string $destination, $path = '/', array $params = [], array $post = [])
    {
        /** @var \Nette\Application\Responses\RedirectResponse $response */
        $response = $this->check($destination, $params, $post);
        if (!$this->testbench_exception) {
            Assert::same(200, $this->getReturnCode());
            Assert::type(\Nette\Application\Responses\RedirectResponse::class, $response);
            Assert::same(302, $response->getCode());
            if ($path) {
                if (!Assert::isMatching("~^https?://test\.bench{$path}(?(?=\?).+)$~", $response->getUrl())) {
                    $path = Dumper::color('yellow') . Dumper::toLine($path) . Dumper::color('white');
                    $url = Dumper::color('yellow') . Dumper::toLine($response->getUrl()) . Dumper::color('white');
                    $originalUrl = new \Nette\Http\Url($response->getUrl());
                    Assert::fail(
                        str_repeat(' ', strlen($originalUrl->getHostUrl()) - 13) // strlen('Failed: path ') = 13
                        . "path $path doesn't match\n$url\nafter redirect"
                    );
                }
            }
        }
        return $response;
    }

    /**
     * @param string $destination fully qualified presenter name (module:module:presenter)
     * @param array $params provided to the presenter usually via URL
     * @param array $post provided to the presenter via POST
     *
     * @throws Exception
     */
    protected function checkJson(string $destination, array $params = [], array $post = []): \Nette\Application\Responses\JsonResponse
    {
        /** @var \Nette\Application\Responses\JsonResponse $response */
        $response = $this->check($destination, $params, $post);
        if (!$this->testbench_exception) {
            Assert::same(200, $this->getReturnCode());
            Assert::type(\Nette\Application\Responses\JsonResponse::class, $response);
            Assert::same('application/json', $response->getContentType());
        }
        return $response;
    }

    /**
     * @param string $destination fully qualified presenter name (module:module:presenter)
     * @param array $scheme what is expected
     * @param array $params provided to the presenter usually via URL
     * @param array $post provided to the presenter via POST
     */
    public function checkJsonScheme(string $destination, array $scheme, array $params = [], array $post = []): void
    {
        $response = $this->checkJson($destination, $params, $post);
        Assert::same($scheme, $response->getPayload());
    }

    /**
     * @param string $destination fully qualified presenter name (module:module:presenter)
     * @param array $post provided to the presenter via POST
     * @param string|boolean $path Path after redirect or FALSE if it's form without redirect
     *
     * @throws \Tester\AssertException
     */
    protected function checkForm(string $destination, string $formName, array $post = [], $path = '/'): \Nette\Application\IResponse
    {
        if (is_string($path)) {
            return $this->checkRedirect($destination, $path, [
                'do' => $formName . '-submit',
            ], $post);
        } elseif (is_bool($path)) {
            /** @var \Nette\Application\Responses\RedirectResponse $response */
            $response = $this->check($destination, [
                'do' => $formName . '-submit',
            ], $post);
            if (!$this->testbench_exception) {
                Assert::same(200, $this->getReturnCode());
                Assert::type(\Nette\Application\Responses\TextResponse::class, $response);
            }
            return $response;
        } else {
            Assert::fail('Path should be string or boolean (probably FALSE).');
        }
    }

    /**
     * @param string $destination fully qualified presenter name (module:module:presenter)
     * @param array $post provided to the presenter via POST
     * @param string|bool $path
     *
     * @throws Exception
     */
    protected function checkAjaxForm(string $destination, string $formName, array $post = [], $path = false): \Nette\Application\IResponse
    {
        if (is_string($path)) {
            $this->checkForm($destination, $formName, $post, $path);
            /** @var Presenter $presenter */
            $presenter = $this->testbench_presenter;
            Assert::false($presenter->isAjax());
        }
        $this->testbench_presenter = null; //FIXME: not very nice, but performance first
        $this->testbench_ajaxMode = true;
        $response = $this->check($destination, [
            'do' => $formName . '-submit',
        ], $post);
    /** @var Presenter $presenter */
        $presenter = $this->testbench_presenter;
        Assert::true($presenter->isAjax());
        if (!$this->testbench_exception) {
            Assert::same(200, $this->getReturnCode());
            Assert::type(\Nette\Application\Responses\JsonResponse::class, $response);
        }
        $this->testbench_presenter = null;
        $this->testbench_ajaxMode = false;
        return $response;
    }

    /**
     * @param string $destination fully qualified presenter name (module:module:presenter)
     * @param array $params provided to the presenter usually via URL
     * @param array $post provided to the presenter via POST
     *
     * @throws Exception
     */
    protected function checkRss(string $destination, array $params = [], array $post = []): \Nette\Application\Responses\TextResponse
    {
        /** @var \Nette\Application\Responses\TextResponse $response */
        $response = $this->check($destination, $params, $post);
        if (!$this->testbench_exception) {
            Assert::same(200, $this->getReturnCode());
            Assert::type(\Nette\Application\Responses\TextResponse::class, $response);
            Assert::type(\Nette\Application\UI\ITemplate::class, $response->getSource());

            $dom = \Tester\DomQuery::fromXml((string) $response->getSource());
            Assert::true($dom->has('rss'), "missing 'rss' element");
            Assert::true($dom->has('channel'), "missing 'channel' element");
            Assert::true($dom->has('title'), "missing 'title' element");
            Assert::true($dom->has('link'), "missing 'link' element");
            Assert::true($dom->has('item'), "missing 'item' element");
        }
        return $response;
    }

    /**
     * @param string $destination fully qualified presenter name (module:module:presenter)
     * @param array $params provided to the presenter usually via URL
     * @param array $post provided to the presenter via POST
     *
     * @throws Exception
     */
    protected function checkSitemap(string $destination, array $params = [], array $post = []): \Nette\Application\Responses\TextResponse
    {
        /** @var \Nette\Application\Responses\TextResponse $response */
        $response = $this->check($destination, $params, $post);
        if (!$this->testbench_exception) {
            Assert::same(200, $this->getReturnCode());
            Assert::type(\Nette\Application\Responses\TextResponse::class, $response);
            Assert::type(\Nette\Application\UI\ITemplate::class, $response->getSource());

            $xml = \Tester\DomQuery::fromXml((string) $response->getSource());
            Assert::same('urlset', $xml->getName(), 'root element is');
            $url = $xml->children();
            Assert::same('url', $url->getName(), "child of 'urlset'");
            Assert::same('loc', $url->children()->getName(), "child of 'url'");
        }
        return $response;
    }

    /**
     * @param \Nette\Security\IIdentity|integer $id
     * @param array|null $roles
     * @param array|null $data
     */
    protected function logIn($id = 1, $roles = null, $data = null): \Nette\Security\User
    {
        if ($id instanceof \Nette\Security\IIdentity) {
            $identity = $id;
        } else {
            $identity = new \Nette\Security\Identity($id, $roles, $data);
        }
        /** @var \Nette\Security\User $user */
        $user = ContainerFactory::create(false)->getByType(\Nette\Security\User::class);
        $user->login($identity);
        return $user;
    }

    protected function logOut(): \Nette\Security\User
    {
        /** @var \Nette\Security\User $user */
        $user = ContainerFactory::create(false)->getByType(\Nette\Security\User::class);
        $user->logout();
        return $user;
    }

    protected function isUserLoggedIn(): bool
    {
        /** @var \Nette\Security\User $user */
        $user = ContainerFactory::create(false)->getByType(\Nette\Security\User::class);
        return $user->isLoggedIn();
    }

    /**
     * @return Presenter|null
     */
    protected function getPresenter()
    {
        return $this->testbench_presenter;
    }

    protected function getReturnCode(): int
    {
        return $this->testbench_httpCode;
    }

    protected function getException(): Exception
    {
        return $this->testbench_exception;
    }
}
