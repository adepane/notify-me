<?php

declare(strict_types=1);

namespace NotifyMe\Tests;

use NotifyMe\ExceptionNotifierServiceProvider;
use NotifyMe\Factories\DiscordLoggerFactory;
use NotifyMe\Factories\SlackLoggerFactory;
use NotifyMe\Factories\TelegramLoggerFactory;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Register the package service provider in the Testbench app.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [ExceptionNotifierServiceProvider::class];
    }

    /**
     * Configure the host application's environment for the test suite.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.env', 'testing');
        $app['config']->set('app.url', 'https://example.test');

        // Package credentials used by the factories.
        $app['config']->set('exception-notifier.telegram.bot_token', 'TEST_TOKEN');
        $app['config']->set('exception-notifier.telegram.chat_id', '123456');
        $app['config']->set('exception-notifier.discord.webhook_url', 'https://discord.test/webhook');
        $app['config']->set('exception-notifier.slack.webhook_url', 'https://slack.test/webhook');

        // Register the three custom logging channels the package powers.
        $app['config']->set('logging.channels.telegram', [
            'driver' => 'custom',
            'via' => TelegramLoggerFactory::class,
            'level' => 'error',
        ]);
        $app['config']->set('logging.channels.discord', [
            'driver' => 'custom',
            'via' => DiscordLoggerFactory::class,
            'level' => 'error',
        ]);
        $app['config']->set('logging.channels.slack', [
            'driver' => 'custom',
            'via' => SlackLoggerFactory::class,
            'level' => 'error',
        ]);

        // A stack so we can exercise multiple channels simultaneously.
        $app['config']->set('logging.channels.notifiers', [
            'driver' => 'stack',
            'channels' => ['telegram', 'discord', 'slack'],
        ]);
    }
}
