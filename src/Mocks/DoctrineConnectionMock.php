<?php

declare(strict_types=1);

namespace Testbench\Mocks;

use Doctrine\Common;
use Doctrine\DBAL;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Kdyby\Doctrine\Connection;
use Nette\UnexpectedValueException;

class DoctrineConnectionMock extends Connection implements \Testbench\Providers\IDatabaseProvider
{
    private ?string $testbench_databaseName = null;

    /** @var callable[] */
    public $onConnect = [];

  /**
   * @return void
   */
    public function onConnect(self $self)
    {
        if (is_array($this->onConnect) || $this->onConnect instanceof \Traversable) {
            foreach ($this->onConnect as $handler) {
                $handler($self);
            }
        } elseif ($this->onConnect !== null) {
            throw new UnexpectedValueException("Property " . static::class . "::\$onConnect must be array or null, " . gettype($this->onConnect) . ' given.');
        }
    }

  /**
   * @return bool
   */
    public function connect()
    {
        if (parent::connect()) {
            $this->onConnect($this);
            return true;
        }
        return false;
    }

    public function __construct(
        array $params,
        DBAL\Driver $driver,
        DBAL\Configuration $config = null,
        Common\EventManager $eventManager = null
    ) {
        $container = \Testbench\ContainerFactory::create(false);
        $this->onConnect[] = function (DoctrineConnectionMock $connection) use ($container) {
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
            } catch (\Doctrine\DBAL\Migrations\MigrationException $e) {
              //  do not throw an exception if there are no migrations
                if ($e->getCode() !== 4) {
                    \Tester\Assert::fail($e->getMessage());
                }
            } catch (\Exception $e) {
                \Tester\Assert::fail($e->getMessage());
            }
        };
        parent::__construct($params, $driver, $config, $eventManager);
    }

  /**
   * @param Connection $connection
   * @throws DBAL\Migrations\MigrationException
   * @internal
   */
    public function __testbench_database_setup($connection, \Nette\DI\Container $container, $persistent = false)
    {
        $config = $container->parameters['testbench'];
        $this->testbench_databaseName = $config['dbprefix'] . getenv(\Tester\Environment::THREAD);

        $this->__testbench_database_drop($connection, $container);
        $this->__testbench_database_create($connection, $container);

        foreach ($config['sqls'] as $file) {
            \Kdyby\Doctrine\Dbal\BatchImport\Helpers::loadFromFile($connection, $file);
        }

        if ($config['migrations'] === true) {
            if (class_exists(\Nettrine\Migrations\ContainerAwareConfiguration::class)) {
                /** @var \Nettrine\Migrations\ContainerAwareConfiguration $migrationsConfig */
                $migrationsConfig = $container->getByType(\Nettrine\Migrations\ContainerAwareConfiguration::class);
                $migrationsConfig->__construct($connection);
                $migrationsConfig->registerMigrationsFromDirectory($migrationsConfig->getMigrationsDirectory());
                $migration = new \Doctrine\DBAL\Migrations\Migration($migrationsConfig);
                $migration->migrate($migrationsConfig->getLatestVersion());
            }
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
        $connection->exec("CREATE DATABASE {$this->testbench_databaseName}");
        $this->__testbench_database_change($connection, $container);
    }

    /**
     * @param Connection $connection
   * @internal
     *
     */
    public function __testbench_database_change($connection, \Nette\DI\Container $container)
    {
        if ($connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $connection->exec("USE {$this->testbench_databaseName}");
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
        if (!$connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $this->__testbench_database_connect($connection, $container);
        }
        $connection->exec("DROP DATABASE IF EXISTS {$this->testbench_databaseName}");
    }

    /**
     *@internal
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
            } elseif ($connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
                $databaseName = 'postgres';
            } else {
                throw new \LogicException('You should setup existing database name using testbench:dbname option.');
            }
        }

        $connection->close();
        $connection->__construct(
            ['dbname' => $databaseName] + $connection->getParams(),
            $connection->getDriver(),
            $connection->getConfiguration(),
            $connection->getEventManager()
        );
        $connection->connect();
    }
}
