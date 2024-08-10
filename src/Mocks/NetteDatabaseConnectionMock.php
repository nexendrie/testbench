<?php

declare(strict_types=1);

namespace Testbench\Mocks;

use Nette\Database\Connection;
use Nette\Database\Drivers\MySqlDriver;
use Nette\Database\Drivers\PgSqlDriver;
use Nette\Reflection\ClassType;

/**
 * @method onConnect(NetteDatabaseConnectionMock $connection)
 */
class NetteDatabaseConnectionMock extends Connection implements \Testbench\Providers\IDatabaseProvider
{
    private ?string $testbench_databaseName = null;

  /**
   * NetteDatabaseConnectionMock constructor.
   * @param string|null $dsn
   * @param string|null $user
   * @param string|null $password
   * @param array|null $options
   */
    public function __construct($dsn, $user = null, $password = null, array $options = null)
    {
        $container = \Testbench\ContainerFactory::create(false);
        $this->onConnect[] = function (NetteDatabaseConnectionMock $connection) use ($container) {
            if ($this->testbench_databaseName !== null) { //already initialized (needed for pgsql)
                return;
            }
            try {
                $config = $container->parameters['testbench'];
                if ($config['shareDatabase'] === true) {
                    $registry = new \Testbench\DatabasesRegistry();
                    $dbName = $container->parameters['testbench']['dbprefix'] . getenv(\Tester\Environment::THREAD);
                    if ($registry->registerDatabase($dbName)) {
                        $this->__testbench_database_setup($connection, $container, true);
                    } else {
                        $this->testbench_databaseName = $dbName;
                        $this->__testbench_database_change($connection, $container);
                    }
                } else { // always create new test database
                    $this->__testbench_database_setup($connection, $container);
                }
            } catch (\Exception $e) {
                \Tester\Assert::fail($e->getMessage());
            }
        };
        parent::__construct($dsn, $user, $password, $options);
    }

  /**
   * @internal
   *
   * @param Connection $connection
   */
    public function __testbench_database_setup($connection, \Nette\DI\Container $container, $persistent = false)
    {
        $config = $container->parameters['testbench'];
        $this->testbench_databaseName = $config['dbprefix'] . getenv(\Tester\Environment::THREAD);

        $this->__testbench_database_drop($connection, $container);
        $this->__testbench_database_create($connection, $container);

        foreach ($config['sqls'] as $file) {
            \Nette\Database\Helpers::loadFromFile($connection, $file);
        }

        if ($persistent === false) {
            register_shutdown_function(function () use ($connection, $container) {
                $this->__testbench_database_drop($connection, $container);
            });
        }
    }

    /**
     * @internal
     *
     * @param Connection $connection
     */
    public function __testbench_database_create($connection, \Nette\DI\Container $container)
    {
        $connection->query("CREATE DATABASE {$this->testbench_databaseName}");
        $this->__testbench_database_change($connection, $container);
    }

    /**
     * @internal
     *
     * @param Connection $connection
     */
    public function __testbench_database_change($connection, \Nette\DI\Container $container)
    {
        if ($connection->getSupplementalDriver() instanceof MySqlDriver) {
            $connection->query("USE {$this->testbench_databaseName}");
        } else {
            $this->__testbench_database_connect($connection, $container, $this->testbench_databaseName);
        }
    }

    /**
     * @internal
     *
     * @param Connection $connection
     */
    public function __testbench_database_drop($connection, \Nette\DI\Container $container)
    {
        if (!$connection->getSupplementalDriver() instanceof MySqlDriver) {
            $this->__testbench_database_connect($connection, $container);
        }
        $connection->query("DROP DATABASE IF EXISTS {$this->testbench_databaseName}");
    }

    /**
     * @internal
     *
     * @param Connection $connection
     */
    public function __testbench_database_connect($connection, \Nette\DI\Container $container, $databaseName = null)
    {
        //connect to an existing database other than $this->_databaseName
        if ($databaseName === null) {
            $dbname = $container->parameters['testbench']['dbname'];
            if ($dbname) {
                $databaseName = $dbname;
            } elseif ($connection->getSupplementalDriver() instanceof PgSqlDriver) {
                $databaseName = 'postgres';
            } else {
                throw new \LogicException('You should setup existing database name using testbench:dbname option.');
            }
        }

        $dsn = preg_replace('~dbname=[a-z0-9_-]+~i', "dbname=$databaseName", $connection->getDsn());

        /** @var ClassType $dbr */
        $dbr = (new ClassType($connection))->getParentClass(); //:-(
        $params = $dbr->getProperty('params');
        $params->setAccessible(true);
        $params = $params->getValue($connection);

        $options = $dbr->getProperty('options');
        $options->setAccessible(true);
        $options = $options->getValue($connection);

        $connection->disconnect();
        $connection->__construct($dsn, $params[1], $params[2], $options);
        $connection->connect();
    }
}
