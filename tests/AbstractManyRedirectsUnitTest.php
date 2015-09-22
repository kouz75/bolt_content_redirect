<?php
namespace Bolt\Extension\SthlmConnection\ManyRedirects\Tests;

use Bolt\Tests\BoltUnitTest;
use Bolt\Extension\SthlmConnection\ManyRedirects\Extension;
use Bolt\Extension\SthlmConnection\ManyRedirects\Redirect;

abstract class AbstractManyRedirectsUnitTest extends BoltUnitTest {
  protected $app;

  protected $extension;

  protected function setup() {
    $this->app = $this->getApp();
    $this->extension = new Extension($this->app);
    $this->app['extensions']->register($this->extension);
    $this->extension->dbCheck(); // Register the database table.
    $this->app['integritychecker']->repairTables();

    Redirect::$dbConnection = $this->app['db'];
    Redirect::$tableName = $this->extension->getTableName();
  }
}
