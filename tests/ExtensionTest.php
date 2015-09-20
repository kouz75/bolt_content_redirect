<?php

namespace Bolt\Extension\SthlmConnection\ManyRedirects\Tests;

use Bolt\Tests\BoltUnitTest;
use Bolt\Content;
use Bolt\Extension\SthlmConnection\ManyRedirects\Extension;
use Bolt\Extension\SthlmConnection\ManyRedirects\Redirect;
use Bolt\Extension\SthlmConnection\ManyRedirects\Tests\AbstractManyRedirectsUnitTest;
use Symfony\Component\HttpFoundation\Request;

class ExtensionTest extends AbstractManyRedirectsUnitTest {
  public function testExtensionLoads() {
    $name = $this->extension->getName();
    $this->assertSame($name, 'ManyRedirects');
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
    $redirect = new Redirect($this->app['db'], $this->extension->tableName, $values);
    $redirect->save();

    $request = Request::create('/test');

    $response = $this->app->handle($request);
    $this->assertTrue($response->isRedirect('http:///entry/test'));
  }
}
