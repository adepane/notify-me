<?php

declare(strict_types=1);

namespace NotifyMe\Factories;

use Monolog\Level;
use NotifyMe\Formatters\ExceptionFormatter;

/**
 * Helpers shared by every logger factory: reads the package config, applies
 * the global defaults and builds a configured ExceptionFormatter.
 */
trait ResolvesNotifierConfig
{
    /**
     * Fetch a channel value, falling back to the package defaults when the
     * channel-specific entry is null/absent.
     *
     * @param  array<string, mixed>  $channelConfig
     */
    protected function setting(array $channelConfig, string $key, mixed $default = null): mixed
    {
        if (isset($channelConfig[$key]) && $channelConfig[$key] !== null) {
            return $channelConfig[$key];
        }

        /** @var array<string, mixed> $defaults */
        $defaults = config('exception-notifier.defaults', []);

        return $defaults[$key] ?? $default;
    }

    /**
     * Build the formatter for a channel from the resolved configuration.
     *
     * @param  array<string, mixed>  $channelConfig
     */
    protected function makeFormatter(array $channelConfig): ExceptionFormatter
    {
        return new ExceptionFormatter(
            format: (string) $this->setting($channelConfig, 'format', 'markdown'),
            maxStackTraceLines: (int) $this->setting($channelConfig, 'max_stack_trace_lines', 15),
            environment: (string) config('app.env', 'production'),
            appUrl: (string) config('app.url', ''),
        );
    }

    /**
     * Resolve the effective log level for a channel.
     *
     * The Laravel logging-channel "level" (passed through $driverConfig) wins;
     * otherwise we use the channel/default package level.
     *
     * @param  array<string, mixed>  $channelConfig
     * @param  array<string, mixed>  $driverConfig
     */
    protected function resolveLevel(array $channelConfig, array $driverConfig): Level
    {
        $level = $driverConfig['level']
            ?? $this->setting($channelConfig, 'level', 'error');

        return Level::fromName(ucfirst((string) $level));
    }
}
