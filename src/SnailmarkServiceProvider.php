<?php

namespace Snailmark\Mail;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;

class SnailmarkServiceProvider extends ServiceProvider
{
    /**
     * Register the "snailmark" mail transport with the mail manager.
     */
    public function boot(): void
    {
        $this->app['mail.manager']->extend('snailmark', function (array $config) {
            $configuration = $this->app->make('config');

            return new SnailmarkTransport(
                $this->app->make(Factory::class),
                $config['message_stream_id'] ?? $configuration->get('services.snailmark.message_stream'),
                $configuration->get('services.snailmark.options', []),
                $configuration->get('services.snailmark.token'),
                $configuration->get('services.snailmark.base_url', 'https://api.snailmark.com'),
            );
        });
    }
}
