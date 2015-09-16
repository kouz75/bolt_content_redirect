<?php

namespace Bolt\Extension\SthlmConnection\ManyRedirects;

use Bolt\Application;
use Bolt\BaseExtension;
use Silex\Application as BoltApplication;
use Symfony\Component\HttpFoundation\Request;

class Extension extends BaseExtension {

  public function __construct(Application $app) {
    parent::__construct($app);
  }

  public function getName() {
    return "ManyRedirects";
  }

  public function initialize() {
    $self = $this;

    // Register this extension's actions as an early event
    $this->app->before(function (Request $request) use ($self) {
      if ($self->dbCheck()) {
        $self->handleRequest($request);
      }
    }, BoltApplication::EARLY_EVENT);
  }

  public function handleRequest(Request $request) {
    $app = $this->app;

    // Look for a migrated article with this URL path.
    $requested_path = $request->getPathInfo();
    $redirect = $this->getRedirect($requested_path);

    if (!empty($redirect)) {
      $status_code = $redirect['code'];
      $status_code = empty($status_code) ? $this->config['status_code'] : $status_code;
      $status_code = !in_array($status_code, [301, 302]) ? 302 : $status_code;
      $record = $this->getContentRecord($redirect['content_type'], $redirect['content_id']);
      if ($record) {
        return $app->redirect($app['paths']['rooturl'] . strtolower($record->getReference()), $status_code);
      }
      else {
        return false;
      }
    }
  }

  public function saveRedirect($source, $content_type, $content_id, $code = null) {
    $exists = $this->getRedirect($source);

    $values = array(
      'source' => $source,
      'content_type' => $content_type,
      'content_id' => $content_id,
      'code' => $code,
    );

    if ($exists) {
      $result = $this->app['db']->update($this->getTableName(), $values, $values);
    } else {
      $result = $this->app['db']->insert($this->getTableName(), $values);
    }

    return !!$result;
  }

  public function getRedirect($source) {
    $query = 'SELECT * FROM ' . $this->getTableName() . ' WHERE source = ?';
    $record = $this->app['db']->fetchAssoc($query, array($source));
    return empty($record) ? NULL : $record;
  }

  public function getContentRecord($content_type, $content_id) {
    $prefix = $this->app['config']->get('general/database/prefix', 'bolt_');
    $table = $prefix . $content_type;
    $query = "SELECT * FROM $table WHERE id = ?";
    $db_values = $this->app['db']->fetchAssoc($query, array($content_id));
    $record = $this->app['storage']->getContentObject($content_type, $db_values);
    if (!$record) {
      $this->app['logger.system']->error("MigrationRedirects: couldn't find content id '$content_id'.", ['event' => 'migrationredirects']);
    }
    return $record;
  }

  public function getTableName() {
    $prefix = $this->app['config']->get('general/database/prefix', 'bolt_');
    if (substr($prefix, -1, 1) != "_") {
      $prefix .= "_";
    }
    return $prefix . 'many_redirects';
  }

  public function dbCheck() {
    $table_name = $this->getTableName();
    $this->app['integritychecker']->registerExtensionTable(
      function ($schema) use ($table_name) {
        $table = $schema->createTable($table_name);
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
    return $schema_manager->tablesExist(array($table_name));
  }

}
