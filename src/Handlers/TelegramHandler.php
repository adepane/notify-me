<?php

declare(strict_types=1);

namespace NotifyMe\Handlers;

use Illuminate\Support\Facades\Http;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Throwable;

/**
 * Delivers formatted log records to a Telegram chat via the Bot API.
 */
final class TelegramHandler extends AbstractProcessingHandler
{
    use HandlesNotificationFailures;

    /** Telegram rejects messages longer than 4096 characters. */
    private const MAX_MESSAGE_LENGTH = 4096;

    public function __construct(
        private readonly string $botToken,
        private readonly string $chatId,
        private readonly string $parseMode = 'Markdown',
        private readonly int $timeout = 5,
        int|string|Level $level = Level::Error,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    /**
     * Send one record. Any failure is logged, never thrown.
     */
    protected function write(LogRecord $record): void
    {
        // Missing credentials should disable the channel quietly.
        if ($this->botToken === '' || $this->chatId === '') {
            $this->reportFailure('Telegram', 'missing bot token or chat id');

            return;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->asJson()
                ->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                    'chat_id' => $this->chatId,
                    'text' => $this->truncate((string) $record->formatted),
                    'parse_mode' => $this->parseMode,
                    'disable_web_page_preview' => true,
                ]);

            if ($response->failed()) {
                $this->reportFailure('Telegram', "HTTP {$response->status()}: {$response->body()}");
            }
        } catch (Throwable $e) {
            $this->reportFailure('Telegram', 'request threw an exception', $e);
        }
    }

    private function truncate(string $text): string
    {
        return mb_strlen($text) <= self::MAX_MESSAGE_LENGTH
            ? $text
            : mb_substr($text, 0, self::MAX_MESSAGE_LENGTH - 1).'…';
    }
}
