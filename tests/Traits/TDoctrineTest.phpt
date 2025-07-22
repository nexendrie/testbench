<?php

declare(strict_types=1);

namespace Tests\Traits;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Testbench\Mocks\DoctrineConnectionMock;
use Tester\Assert;

require getenv('BOOTSTRAP');

/**
 * @testCase
 */
class TDoctrineTest extends \Tester\TestCase
{
    use \Testbench\TCompiledContainer;
    use \Testbench\TDoctrine;

    public function testLazyConnection(): void
    {
        $container = $this->getContainer();
        /** @var DoctrineConnectionMock $db */
        $db = $container->getByType(\Doctrine\DBAL\Connection::class);
        $db->onConnect[] = function () {
            Assert::fail(\Testbench\Mocks\DoctrineConnectionMock::class . '::$onConnect event should not be called if you do NOT need database');
        };
        \Tester\Environment::$checkAssertions = false;
    }

    public function testEntityManager(): void
    {
        Assert::type(\Doctrine\ORM\EntityManagerInterface::class, $this->getEntityManager());
    }

    public function testDatabaseCreation(): void
    {
        /** @var \Testbench\Mocks\DoctrineConnectionMock $connection */
        $connection = $this->getEntityManager()->getConnection();
        if ($connection->getDatabasePlatform() instanceof MySqlPlatform) {
            Assert::match('information_schema', $connection->getDatabase());
            Assert::match('_testbench_' . getenv(\Tester\Environment::THREAD), $connection->query('SELECT DATABASE();')->fetchColumn());
        } else {
            Assert::same('_testbench_' . getenv(\Tester\Environment::THREAD), $connection->getDatabase());
        }
    }

    public function testDatabaseSqls(): void
    {
        /** @var \Testbench\Mocks\DoctrineConnectionMock $connection */
        $connection = $this->getEntityManager()->getConnection();
        $result = $connection->query('SELECT * FROM table_1')->fetchAll();
        if ($connection->getDatabasePlatform() instanceof MySqlPlatform) {
            Assert::same([
                ['id' => '1', 'column_1' => 'value_1', 'column_2' => 'value_2'],
                ['id' => '2', 'column_1' => 'value_1', 'column_2' => 'value_2'],
                ['id' => '3', 'column_1' => 'value_1', 'column_2' => 'value_2'],
                [
                    'id' => '4',
                    'column_1' => 'from_migration_1',
                    'column_2' => 'from_migration_2',
                ],
            ], $result);
            Assert::match('information_schema', $connection->getDatabase());
        } else {
            Assert::same([
                ['id' => 1, 'column_1' => 'value_1', 'column_2' => 'value_2'],
                ['id' => 2, 'column_1' => 'value_1', 'column_2' => 'value_2'],
                ['id' => 3, 'column_1' => 'value_1', 'column_2' => 'value_2'],
            ], $result);
            Assert::same('_testbench_' . getenv(\Tester\Environment::THREAD), $connection->getDatabase());
        }
    }

    public function testDatabaseConnectionReplacementInApp(): void
    {
        /** @var \Kdyby\Doctrine\EntityManager $em */
        $em = $this->getService(\Kdyby\Doctrine\EntityManager::class);
        new \DoctrineComponentWithDatabaseAccess($em); //tests inside
        //app is not using onConnect from Testbench but it has to connect to the mock database
    }
}

(new TDoctrineTest())->run();
