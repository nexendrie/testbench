<?php

declare(strict_types=1);

namespace Testbench\Providers;

/**
 * This interface is not stable yet. In fact it's really bad design and it needs refactor (stay tuned).
 */
interface IDatabaseProvider
{

  /**
   * Perform complete database setup (should drop and create database, import sqls, run migrations).
   * Register shutdown function only if it's not persistent setup.
   *
   * @param object $connection
   * @param bool $persistent
   * @return void
   */
    public function __testbench_database_setup($connection, \Nette\DI\Container $container, $persistent = false);

  /**
   * Drop database.
   * This function uses internal '__testbench_databaseName'. Needs refactor!
   *
   * @param object $connection
   * @return void
   */
    public function __testbench_database_drop($connection, \Nette\DI\Container $container);

  /**
   * Create new database.
   * This function uses internal '__testbench_databaseName'. Needs refactor!
   *
   * @param object $connection
   * @return void
   */
    public function __testbench_database_create($connection, \Nette\DI\Container $container);

  /**
   * Connect to the database.
   * This function uses internal '__testbench_databaseName'. Needs refactor!
   *
   * @param object $connection
   * @param string|null $databaseName
   * @return void
   */
    public function __testbench_database_connect($connection, \Nette\DI\Container $container, $databaseName = null);

  /**
   * Change database as quickly as possible (USE in MySQL, connect in PostgreSQL).
   * This function uses internal '__testbench_databaseName'. Needs refactor!
   *
   * @param object $connection
   * @return void
   */
    public function __testbench_database_change($connection, \Nette\DI\Container $container);
}
