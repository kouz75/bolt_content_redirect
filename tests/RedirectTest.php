<?php

namespace Bolt\Extension\SthlmConnection\ManyRedirects\Tests;

use Bolt\Tests\BoltUnitTest;
use Bolt\Extension\SthlmConnection\ManyRedirects\Redirect;
use Bolt\Extension\SthlmConnection\ManyRedirects\Tests\AbstractManyRedirectsUnitTest;


class RedirectTest extends AbstractManyRedirectsUnitTest {
  public function testSaveRedirect() {
    $values = [
      'source' => '/redirect-test',
      'contentId' => 1,
      'contentType' => 'entries',
      'code' => 302,
    ];
    $redirect = new Redirect($this->app['db'], $this->extension->tableName, $values);
    $redirect->save();

    $new = new Redirect($this->app['db'], $this->extension->tableName);
    $this->assertTrue($new->load('/redirect-test'));
  }

  public function testThrowsExceptionOnInvalidCode() {
    $this->setExpectedException('\InvalidArgumentException');

    $values = [
      'source' => '/test-3',
      'contentId' => 1,
      'contentType' => 'entries',
      'code' => 200,
    ];
    $redirect = new Redirect($this->app['db'], $this->extension->tableName, $values);
    $redirect->save();
  }

}
