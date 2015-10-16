<?php

namespace Bolt\Extension\SthlmConnection\ContentRedirect;

use Bolt\Application;
use Bolt\BaseExtension;
use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Request;

class Extension extends BaseExtension {

  public function __construct(Application $app) {
    parent::__construct($app);
  }

  public function getName() {
    return "ContentRedirect";
  }

  public function initialize() {
    Redirect::$dbConnection = $this->app['db'];
    Redirect::$tableName = $this->getTableName();

    $extension = $this;

    // Register this extension's actions as an early event.
    $this->app->before(function (Request $request) use ($extension) {
      if ($extension->dbCheck()) {
        return $extension->handleRequest($request);
      }
    }, SilexApplication::EARLY_EVENT);
  }

  public function handleRequest(Request $request) {
    // Look for a migrated article with this URL path.
    $requested_path = $request->getPathInfo();

    if ($this->isRedirectable($requested_path) && $redirect = Redirect::load($requested_path)) {
      $status_code = $redirect->code;
      $status_code = empty($status_code) ? $this->config['default_status_code'] : $status_code;
      if (!in_array($status_code, Redirect::$validCodes)) {
        $status_code = 302;
        $this->app['logger.system']->error("Prevented an invalid HTTP code ($status_code) from being sent for '$requested_path'. Instead, used 302 as fallback.", ['event' => 'contentredirect']);
      }

      $record = $this->app['storage']->getContent("$redirect->contentType/$redirect->contentId");
      if ($record) {
        $root_path = $this->app['paths']['root'];
        $record_path = substr($record->link(), 1);  // Strip off the first '/'.
        return $this->app->redirect($root_path . $record_path, $status_code);
      }
      else {
        $this->app['logger.system']->error("Couldn't find content with type '$content_type' and id '$content_id'.", ['event' => 'contentredirect']);
        return false;
      }
    }
  }

  public function isRedirectable($path) {
    $blacklist = [
      '/^\/$/',
      '/^\/bolt.*$/',
    ];
    foreach ($blacklist as $pattern) {
      if (preg_match($pattern, $path)) {
        return false;
      }
    }
    return true;
  }

  public function dbCheck() {
    $table = $this->getTableName();
    $this->app['integritychecker']->registerExtensionTable(
      function ($schema) use ($table) {
        $table = $schema->createTable($table);
        $table->addColumn('id', 'integer', array('autoincrement' => true));
        $table->addColumn('source', 'string', array('length' => 128));
        $table->addColumn('content_type', 'string', array('length' => 128));
        $table->addColumn('content_id', 'integer');
        $table->addColumn('code', 'integer', array('length' => 3, 'default' => null, 'notnull' => false));
        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(array('source'));
        $table->addIndex(array('content_type', 'content_id'));
        return $table;
      }
    );

    // The table is not immediately created, so check if exists and return that.
    $schema_manager = $this->app['db']->getSchemaManager();
    return $schema_manager->tablesExist(array($table));
  }

  public function getTableName() {
    $prefix = $this->app['config']->get('general/database/prefix', 'bolt_');
    if (substr($prefix, -1, 1) != "_") {
      $prefix .= "_";
    }
    return $prefix . 'content_redirect';
  }

}
