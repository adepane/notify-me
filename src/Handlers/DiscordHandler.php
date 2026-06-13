<?php

declare(strict_types=1);

namespace NotifyMe\Handlers;

use Illuminate\Support\Facades\Http;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Throwable;

/**
 * Delivers formatted log records to a Discord channel via an incoming webhook.
 */
final class DiscordHandler extends AbstractProcessingHandler
{
    use HandlesNotificationFailures;

    /** Discord rejects webhook content longer than 2000 characters. */
    private const MAX_MESSAGE_LENGTH = 2000;

    public function __construct(
        private readonly string $webhookUrl,
        private readonly int $timeout = 5,
        int|string|Level $level = Level::Error,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        if ($this->webhookUrl === '') {
            $this->reportFailure('Discord', 'missing webhook url');

            return;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->asJson()
                ->post($this->webhookUrl, [
                    'content' => $this->truncate((string) $record->formatted),
                ]);

            if ($response->failed()) {
                $this->reportFailure('Discord', "HTTP {$response->status()}: {$response->body()}");
            }
        } catch (Throwable $e) {
            $this->reportFailure('Discord', 'request threw an exception', $e);
        }
    }

    private function truncate(string $text): string
    {
        return mb_strlen($text) <= self::MAX_MESSAGE_LENGTH
            ? $text
            : mb_substr($text, 0, self::MAX_MESSAGE_LENGTH - 1).'…';
    }
}
