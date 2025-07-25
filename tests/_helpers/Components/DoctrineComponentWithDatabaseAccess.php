<?php

declare(strict_types=1);

namespace Testbench\Components;

use Tester\Assert;

/**
 * @property \Nette\Bridges\ApplicationLatte\Template $template
 */
class DoctrineComponentWithDatabaseAccess extends \Nette\Application\UI\Control
{
    public function __construct(\Kdyby\Doctrine\EntityManager $entityManager)
    {
        parent::__construct();

        /** @var \Testbench\Mocks\DoctrineConnectionMock $connection */
        $connection = $entityManager->getConnection();
        Assert::type(\Testbench\Mocks\DoctrineConnectionMock::class, $connection); //not a service (listeners will not work)!
        Assert::false($connection->isConnected());
        Assert::count(1, $connection->onConnect);
        if ($connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySqlPlatform) {
            Assert::match('information_schema', $connection->getDatabase());
            Assert::match('_testbench_' . getenv(\Tester\Environment::THREAD), $connection->query('SELECT DATABASE();')->fetchColumn());
        } else {
            Assert::same('_testbench_' . getenv(\Tester\Environment::THREAD), $connection->getDatabase());
        }
    }

    public function render(): void
    {
        $this->template->render(__DIR__ . '/Component.latte');
    }
}
