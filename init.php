<?php

namespace Bolt\Extension\SthlmConnection\ContentRedirect;

if (isset($app)) {
  $app['extensions']->register(new Extension($app));
}
