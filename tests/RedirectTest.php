<?php

namespace Bolt\Extension\SthlmConnection\ContentRedirect\Tests;

use Bolt\Tests\BoltUnitTest;
use Bolt\Extension\SthlmConnection\ContentRedirect\Redirect;
use Bolt\Extension\SthlmConnection\ContentRedirect\Tests\AbstractContentRedirectUnitTest;


class RedirectTest extends AbstractContentRedirectUnitTest {
  public function testSaveRedirect() {
    $values = [
      'source' => '/redirect-test',
      'contentId' => 1,
      'contentType' => 'entries',
      'code' => 302,
    ];
    $redirect = new Redirect($values);
    $redirect->save();

    $new = Redirect::load('/redirect-test');
    $this->assertEquals(get_class($new), 'Bolt\Extension\SthlmConnection\ContentRedirect\Redirect');
  }

  public function testDuplicates() {
    for ($id = 1; $id < 3; $id++) {
      $values = [
        'source' => '/redirect-test-duplicates',
        'contentId' => $id,
        'contentType' => 'entries',
        'code' => 302,
      ];
      $redirect = new Redirect($values);
      $redirect->save();
    }

    $new = Redirect::load('/redirect-test-duplicates');
    $this->assertEquals($new->contentId, 2);
  }

  public function testThrowsExceptionOnInvalidCode() {
    $this->setExpectedException('\InvalidArgumentException');

    $values = [
      'source' => '/test-3',
      'contentId' => 1,
      'contentType' => 'entries',
      'code' => 200,
    ];
    $redirect = new Redirect($values);
    $redirect->save();
  }

}
