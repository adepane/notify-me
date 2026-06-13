<?php

declare(strict_types=1);

use Monolog\Formatter\FormatterInterface;
use Monolog\Level;
use Monolog\LogRecord;
use NotifyMe\Tests\TestCase;

// Both suites boot a Testbench application: the Http/Log facades and the
// config() helper used by the handlers require a live Laravel container.
uses(TestCase::class)->in('Feature', 'Unit');

/**
 * Build a Monolog LogRecord for use in unit tests.
 *
 * @param  array<string, mixed>  $context
 */
function makeRecord(
    string $message = 'Something broke',
    Level $level = Level::Error,
    array $context = [],
    string $formatted = 'formatted-message',
): LogRecord {
    $record = new LogRecord(
        datetime: new DateTimeImmutable('2026-06-13 10:00:00'),
        channel: 'testing',
        level: $level,
        message: $message,
        context: $context,
    );

    // AbstractProcessingHandler populates "formatted" before write(); we mimic
    // that here so handler unit tests receive a ready-to-send payload.
    return $record->with(formatted: $formatted);
}

/**
 * A formatter that always emits a fixed string.
 *
 * Handlers run their formatter during handle(), overwriting any pre-set
 * "formatted" value. Setting this on a handler gives deterministic message
 * text so payload-structure assertions are not coupled to the default
 * LineFormatter output.
 */
function passthroughFormatter(string $text): FormatterInterface
{
    return new class ($text) implements FormatterInterface {
        public function __construct(private readonly string $text)
        {
        }

        public function format(LogRecord $record): string
        {
            return $this->text;
        }

        /**
         * @param  array<int, LogRecord>  $records
         */
        public function formatBatch(array $records): string
        {
            return $this->text;
        }
    };
}
