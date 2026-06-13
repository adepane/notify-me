<?php

declare(strict_types=1);

use Monolog\Level;
use NotifyMe\Formatters\ExceptionFormatter;

function recordWithException(Throwable $e, Level $level = Level::Error): \Monolog\LogRecord
{
    return new \Monolog\LogRecord(
        datetime: new DateTimeImmutable('2026-06-13 10:00:00'),
        channel: 'testing',
        level: $level,
        message: $e->getMessage(),
        context: ['exception' => $e],
    );
}

it('includes every diagnostic field in the output', function (): void {
    $formatter = new ExceptionFormatter('markdown', 15, 'production', 'https://app.test');
    $exception = new RuntimeException('Database is on fire');

    $output = $formatter->format(recordWithException($exception));

    expect($output)
        ->toContain('RuntimeException')              // exception class
        ->toContain('Database is on fire')           // message
        ->toContain(basename(__FILE__))              // file
        ->toContain('production')                    // environment
        ->toContain('https://app.test')              // app url
        ->toContain('2026-06-13 10:00:00')           // timestamp
        ->toContain('ERROR');                        // level
});

it('truncates the stack trace to the configured number of lines', function (): void {
    $maxLines = 3;
    $formatter = new ExceptionFormatter('plain', $maxLines, 'testing', 'https://app.test');

    // A deeply nested call stack guarantees more than $maxLines trace lines.
    $deep = function (int $depth) use (&$deep) {
        if ($depth === 0) {
            throw new RuntimeException('deep');
        }

        return $deep($depth - 1);
    };

    try {
        $deep(20);
    } catch (RuntimeException $e) {
        $output = $formatter->format(recordWithException($e));
    }

    expect($output)->toContain('more lines truncated');

    // The trace block should not contain more kept frames than configured.
    preg_match('/Stack trace:\n(.*)$/s', $output, $matches);
    $traceLines = array_filter(explode("\n", trim($matches[1] ?? '')));
    // $maxLines kept frames + 1 truncation notice line.
    expect(count($traceLines))->toBeLessThanOrEqual($maxLines + 1);
});

it('renders markdown formatting', function (): void {
    $formatter = new ExceptionFormatter('markdown', 15, 'production', 'https://app.test');

    $output = $formatter->format(recordWithException(new RuntimeException('boom')));

    expect($output)
        ->toContain('*Exception:*')   // markdown bold marker
        ->toContain('```');           // markdown code fence around the trace
});

it('renders plain formatting without markdown markers', function (): void {
    $formatter = new ExceptionFormatter('plain', 15, 'production', 'https://app.test');

    $output = $formatter->format(recordWithException(new RuntimeException('boom')));

    expect($output)
        ->toContain('Exception:')
        ->not->toContain('*Exception:*')
        ->not->toContain('```');
});

it('falls back to the log message when no exception is present', function (): void {
    $formatter = new ExceptionFormatter('plain', 15, 'testing', 'https://app.test');

    $record = new \Monolog\LogRecord(
        datetime: new DateTimeImmutable('2026-06-13 10:00:00'),
        channel: 'testing',
        level: Level::Warning,
        message: 'A plain warning',
        context: [],
    );

    $output = $formatter->format($record);

    expect($output)
        ->toContain('A plain warning')
        ->toContain('Log.WARNING');
});
