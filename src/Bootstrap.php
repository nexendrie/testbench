<?php

declare(strict_types=1);

namespace Testbench;

class Bootstrap
{
  /** @var string */
    public static $tempDir;

    /** @var callable|null */
    public static $onBeforeContainerCreate;

  /**
   * @return void
   */
    public static function setup(string $tempDir, callable $callback = null)
    {
        if (!class_exists(\Tester\Assert::class)) {
            echo "Install Nette Tester using `composer update --dev`\n";
            exit(1);
        }
        self::$tempDir = $tempDir;
        self::$onBeforeContainerCreate = $callback;

        umask(0);
        if (!ob_get_level() > 0) { //\Tester\Environment::setup already called
            \Tester\Environment::setup();
        }
        date_default_timezone_set('Europe/Prague');

        if (class_exists(\Tracy\Debugger::class)) {
            \Tracy\Debugger::$logDirectory = self::$tempDir;
        }

        $_ENV = $_GET = $_POST = $_FILES = [];

        $_SERVER['HTTP_USER_AGENT'] = 'Awesome Browser';
        $_SERVER['REMOTE_ADDR'] = '11.22.33.44';
        $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'test.bench';
    }
}
