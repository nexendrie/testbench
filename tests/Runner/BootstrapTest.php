<?php

declare(strict_types=1);

namespace Tests\Runner;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class BootstrapTest extends \Tester\TestCase
{
    public function testSuperglobals(): void
    {
        Assert::same([], $_ENV);
        Assert::same([], $_GET);
        Assert::same([], $_POST);
        Assert::same([], $_FILES);
    }

    public function testTimezone(): void
    {
        Assert::same('Europe/Prague', date_default_timezone_get());
    }

    public function testBootstrapEnvVariable(): void
    {
        Assert::same(realpath(__DIR__ . '/../bootstrap.php'), getenv('BOOTSTRAP'));
    }

    public function testServerSuperglobalVariable(): void
    {
        Assert::same('Awesome Browser', $_SERVER['HTTP_USER_AGENT']);
        Assert::same('11.22.33.44', $_SERVER['REMOTE_ADDR']);
        Assert::same('test.bench', $_SERVER['HTTP_HOST']);
        Assert::same('test.bench', $_SERVER['SERVER_NAME']);
    }
}

(new BootstrapTest())->run();
