<?php
namespace Bolt\Extension\SthlmConnection\ContentRedirect\Tests;

use Bolt\Tests\BoltUnitTest;
use Bolt\Extension\SthlmConnection\ContentRedirect\Extension;
use Bolt\Extension\SthlmConnection\ContentRedirect\Redirect;

abstract class AbstractContentRedirectUnitTest extends BoltUnitTest {
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
