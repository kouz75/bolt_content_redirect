<?php

namespace Bolt\Extension\SthlmConnection\ContentRedirect\Tests;

use Bolt\Tests\BoltUnitTest;
use Bolt\Content;
use Bolt\Extension\SthlmConnection\ContentRedirect\Extension;
use Bolt\Extension\SthlmConnection\ContentRedirect\Redirect;
use Bolt\Extension\SthlmConnection\ContentRedirect\Tests\AbstractContentRedirectUnitTest;
use Symfony\Component\HttpFoundation\Request;

class ExtensionTest extends AbstractContentRedirectUnitTest {
  public function testExtensionLoads() {
    $name = $this->extension->getName();
    $this->assertSame($name, 'ContentRedirect');
    $this->assertSame($this->extension, $this->app["extensions.$name"]);
  }

  public function testPathRedirects() {
    $entry = new Content($this->app, 'entries');
    $entry->setValue('title', 'Test');
    $entry->setValue('slug', 'test');
    $entry->setValue('ownerid', 1);
    $entry->setValue('status', 'published');
    $id = $this->app['storage']->saveContent($entry);

    $values = [
      'source' => '/test',
      'contentId' => $id,
      'contentType' => 'entries',
      'code' => 302,
    ];
    $redirect = new Redirect($values);
    $redirect->save();

    $request = Request::create('/test');
    $response = $this->app->handle($request);

    $this->assertTrue($response->isRedirect($this->app['paths']['rooturl'] . 'entry/test'));
  }

  public function testDefaultCode() {
    $entry = new Content($this->app, 'entries');
    $entry->setValue('title', 'Test');
    $entry->setValue('slug', 'test-default-code');
    $entry->setValue('ownerid', 1);
    $entry->setValue('status', 'published');
    $id = $this->app['storage']->saveContent($entry);

    $values = [
      'source' => '/test-default-code',
      'contentId' => $id,
      'contentType' => 'entries',
      'code' => null,
    ];
    $redirect = new Redirect($values);
    $redirect->save();

    $request = Request::create('/test-default-code');
    $response = $this->app->handle($request);

    $default = $this->extension->config['default_status_code'];
    $default = empty($default) ? 302 : $default;
    $this->assertEquals($response->getStatusCode(), $default);

  }
}
