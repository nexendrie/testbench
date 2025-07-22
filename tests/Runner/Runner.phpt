<?php

declare(strict_types=1);

namespace Test\Runner;

use Nette\Utils\FileSystem;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class Runner extends \Tester\TestCase
{
    private string $tempDir;

    private \Testbench\Runner $runner;

    public function setUp(): void
    {
        $this->tempDir = dirname(__DIR__) . '/_temp';
        $this->runner = new \Testbench\Runner();
        \Tester\Environment::lock('lock_temp_dir', $this->tempDir); //needed for testConfigExists
    }

    public function testWithoutArguments(): void
    {
        Assert::same([
            '-p', 'php',
            '-s',
            '-C',
            $this->tempDir,
        ], $this->runner->prepareArguments([], $this->tempDir));
    }

    public function testWithoutArgumentsEnv(): void
    {
        Assert::same([
            'ENV=value', //linux environment variable (always first)
            '-p', 'php',
            '-s',
            '-C',
            $this->tempDir,
        ], $this->runner->prepareArguments(['ENV=value'], $this->tempDir));
    }

    public function testWatch(): void
    {
        Assert::same([
            '-w', 'tests/',
            '-w', 'src/',
            '-p', 'php',
            '-s',
            '-C',
            $this->tempDir,
        ], $this->runner->prepareArguments(['-w', 'tests/', '-w', 'src/'], $this->tempDir));
    }

    public function testNativeArguments(): void
    {
        Assert::same([
            '-j', '20',
            '-p', 'php',
            '-s',
            '-C',
            $this->tempDir,
        ], $this->runner->prepareArguments(['-j', '20'], $this->tempDir));
    }

    public function testInterpreter(): void
    {
        Assert::same([
            '-p', 'php-cgi',
            '-s',
            '-C',
            $this->tempDir,
        ], $this->runner->prepareArguments(['-p', 'php-cgi'], $this->tempDir));
    }

    public function testConfigExists(): void
    {
        $os = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'win' : 'unix';
        FileSystem::write($configFile = $this->tempDir . "/php-$os.ini", '');
        Assert::same([
            '-p', 'php',
            '-s',
            '-c', $configFile,
            $this->tempDir,
        ], $this->runner->prepareArguments([], $this->tempDir));
        FileSystem::delete($configFile);
    }

    public function testTemp(): void
    {
        Assert::same([
            '-p', 'php',
            '-s',
            '-C',
            $this->tempDir,
        ], $this->runner->prepareArguments(['--temp', $this->tempDir . '/_temp2'], $this->tempDir));
    }

    public function testPath(): void
    {
        Assert::same([
            '-p', 'php',
            '-s',
            '-C',
            'path/to/tests',
        ], $this->runner->prepareArguments(['path/to/tests'], $this->tempDir));

        Assert::same($expected = [
            '-p', 'php-cgi',
            '-s',
            '-C',
            'path/to/tests',
        ], $this->runner->prepareArguments(['-p', 'php-cgi', 'path/to/tests'], $this->tempDir));
        Assert::same($expected, $this->runner->prepareArguments(['path/to/tests', '-p', 'php-cgi'], $this->tempDir));

        Assert::same([
            '-s',
            '-p', 'php-cgi',
            '-C',
            'path/to/tests',
        ], $this->runner->prepareArguments(['-s', 'path/to/tests', '-p', 'php-cgi'], $this->tempDir));
    }

    public function testAll(): void
    {
        Assert::same([
            'ENV=value', //linux environment variable (always first)
            '-p', 'php-cgi',
            '-s',
            '--stop-on-fail',
            '-w', 'tests/',
            '-w', 'src/',
            '-w', 'folder/',
            '-j', '20',
            '-C',
            'path/to/tests',
        ], $this->runner->prepareArguments([
            'ENV=value',
            '-p', 'php-cgi',
            '-w', 'tests/',
            '-s',
            '--stop-on-fail',
            '-w', 'src/',
            '-w', 'folder/',
            '-j', '20',
            '--temp', $this->tempDir . '/_temp2',
            'path/to/tests'
        ], $this->tempDir));
    }
}

(new Runner())->run();
