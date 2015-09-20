<?php

namespace Bolt\Extension\SthlmConnection\ManyRedirects;

if (isset($app)) {
  $app['extensions']->register(new Extension($app));
}
