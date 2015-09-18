<?php

namespace Bolt\Extension\SthlmConnection\ManyRedirects;

use Bolt\Application;
use Bolt\BaseExtension;
use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Request;

class Extension extends BaseExtension {

  public $tableName;

  public function __construct(Application $app) {
    parent::__construct($app);
  }

  public function getName() {
    return "ManyRedirects";
  }

  public function initialize() {
    $self = $this;

    // Register this extension's actions as an early event.
    $this->app->before(function (Request $request) use ($self) {
      if ($self->dbCheck()) {
        return $self->handleRequest($request);
      }
    }, SilexApplication::EARLY_EVENT);
  }

  public function handleRequest(Request $request) {
    $app = $this->app;
    $redirect = new Redirect($app['db'], $this->tableName);

    // Look for a migrated article with this URL path.
    $requested_path = $request->getPathInfo();

    if ($redirect->load($requested_path)) {
      $status_code = $redirect->code;
      $status_code = empty($status_code) ? $this->config['status_code'] : $status_code;
      $status_code = !in_array($status_code, [301, 302]) ? 302 : $status_code;
      $record = $this->getContentRecord($redirect->contentType, $redirect->contentId);
      if ($record) {
        return $app->redirect($app['paths']['rooturl'] . strtolower($record->getReference()), $status_code);
      }
      else {
        return false;
      }
    }
  }

  public function dbCheck() {
    $table = $this->tableName = $this->getTableName();
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
    return $prefix . 'many_redirects';
  }

  public function getContentRecord($content_type, $content_id) {
    $prefix = $this->app['config']->get('general/database/prefix', 'bolt_');
    $table = $prefix . $content_type;
    $query = "SELECT * FROM $table WHERE id = ?";
    $db_values = $this->app['db']->fetchAssoc($query, array($content_id));
    $record = $this->app['storage']->getContentObject($content_type, $db_values);
    if (!$record) {
      $this->app['logger.system']->error("ManyRedirects: couldn't find content id '$content_id'.", ['event' => 'manyredirects']);
    }
    return $record;
  }

}
