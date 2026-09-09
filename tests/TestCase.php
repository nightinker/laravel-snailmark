<?php

namespace Snailmark\Mail\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Snailmark\Mail\SnailmarkServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [SnailmarkServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('mail.default', 'snailmark');
        $app['config']->set('mail.mailers.snailmark', ['transport' => 'snailmark']);
        $app['config']->set('mail.from', ['address' => 'hello@snailmark.test', 'name' => 'Snailmark']);
        $app['config']->set('services.snailmark.token', 'snl_test_configured');
        $app['config']->set('services.snailmark.base_url', 'https://api.snailmark.test');
        $app['config']->set('services.snailmark.message_stream', 'outbound');
    }
}
