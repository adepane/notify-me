<?php

declare(strict_types=1);

namespace NotifyMe\Handlers;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Shared "never throw" behaviour for notifier handlers.
 *
 * A logging handler must not let a delivery problem bubble up — doing so could
 * abort the very request that produced the original error. Every failure is
 * swallowed and recorded on Laravel's default channel instead, using a
 * dedicated stack so we cannot recurse back into the notifier.
 */
trait HandlesNotificationFailures
{
    /**
     * Record a delivery failure on the default log channel, never re-throwing.
     */
    protected function reportFailure(string $channel, string $reason, ?Throwable $e = null): void
    {
        try {
            Log::channel(config('logging.default', 'stack'))->warning(
                "[notify-me] {$channel} notification failed: {$reason}",
                $e !== null ? ['exception' => $e->getMessage()] : [],
            );
        } catch (Throwable) {
            // If even the fallback logger is unavailable there is nothing left
            // to do — silently give up rather than break the host application.
        }
    }
}
