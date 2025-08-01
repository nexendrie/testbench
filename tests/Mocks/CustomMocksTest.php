<?php

declare(strict_types=1);

namespace Tests\Mocks;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class CustomMocksTest extends \Tester\TestCase
{
    use \Testbench\TCompiledContainer;

    public function testCustomMocks(): void
    {
        Assert::type(\Testbench\Mocks\PresenterMock::class, $this->getService(\Testbench\Mocks\PresenterMock::class));
        Assert::type(\Testbench\CustomPresenterMock::class, $this->getService(\Testbench\Mocks\PresenterMock::class));

        Assert::notSame(\Testbench\Mocks\PresenterMock::class, get_class((object) $this->getService(\Testbench\Mocks\PresenterMock::class)));
        Assert::type(\Testbench\CustomPresenterMock::class, $this->getService(\Testbench\Mocks\PresenterMock::class));
    }
}

(new CustomMocksTest())->run();
