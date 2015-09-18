<?php

namespace Bolt\Extension\SthlmConnection\ManyRedirects;

use Bolt\Application;

class Redirect {

  public $dbConnection;
  public $tableName;
  public $source;
  public $contentId;
  public $contentType;
  public $code;

  public function __construct($db_connection, $table_name, $values = []) {
    $this->dbConnection = $db_connection;
    $this->tableName = $table_name;

    foreach (['source', 'content_id', 'content_type', 'code'] as $key) {
      if (!empty($values[$key])) {
        $this->{$key} = $values[$key];
      }
    }
  }

  public function save() {
    if ($this->code && !in_array($this->code, [301, 302])) {
      $this->code = 302;
    }

    $values = array(
      'source' => $this->source,
      'content_type' => $this->contentType,
      'content_id' => $this->contentId,
      'code' => $this->code,
    );

    if ($this->exists($source)) {
      $result = $this->dbConnection->update($this->tableName, $values, $values);
    } else {
      $result = $this->dbConnection->insert($this->tableName, $values);
    }

    return !!$result;
  }

  public function exists($source) {
    $query = 'SELECT id FROM ' . $this->tableName . ' WHERE source = ?';
    $record = $this->dbConnection->fetchAssoc($query, array($source));

    return !empty($record);
  }

  public function load($source) {
    $query = 'SELECT * FROM ' . $this->tableName . ' WHERE source = ?';
    $record = $this->dbConnection->fetchAssoc($query, array($source));

    if (!empty($record)) {
      $this->source = $record['source'];
      $this->contentId = $record['content_id'];
      $this->contentType = $record['content_type'];
      $this->code = $record['code'];
    }

    return !empty($record);
  }

}
