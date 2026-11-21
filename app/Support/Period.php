<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The date range of a report (today, 7, 30, 90 or 365 days, or custom), the same-length period
 * before it for comparisons, and SQL snippets that work on both MySQL and SQLite.
 */
class Period
{
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public CarbonImmutable $prevFrom,
        public CarbonImmutable $prevTo,
        public string $bucket, // day | month
    ) {}

    public static function fromRequest(Request $request): self
    {
        $request->validate([
            'days' => ['nullable', 'integer', 'in:1,7,30,90,365'],
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $now = CarbonImmutable::now();
        if ($request->filled('from') && $request->filled('to')) {
            $from = CarbonImmutable::parse($request->from)->startOfDay();
            $to = CarbonImmutable::parse($request->to)->endOfDay()->min($now);
        } else {
            $days = (int) ($request->days ?: 30);
            $to = $now;
            $from = $days === 1 ? $now->startOfDay() : $now->subDays($days - 1)->startOfDay();
        }

        $length = $from->diffInSeconds($to);
        $prevTo = $from->subSecond();
        $bucket = $from->diffInDays($to) > 92 ? 'month' : 'day';

        return new self($from, $to, $prevTo->subSeconds((int) $length), $prevTo, $bucket);
    }

    public function range(): array
    {
        return [$this->from, $this->to];
    }

    public function previous(): array
    {
        return [$this->prevFrom, $this->prevTo];
    }

    /** Every bucket key in the range, so charts have no gaps. */
    public function keys(): array
    {
        $keys = [];
        $cursor = $this->bucket === 'month' ? $this->from->startOfMonth() : $this->from->startOfDay();
        while ($cursor <= $this->to) {
            $keys[] = $cursor->format($this->bucket === 'month' ? 'Y-m' : 'Y-m-d');
            $cursor = $this->bucket === 'month' ? $cursor->addMonth() : $cursor->addDay();
        }

        return $keys;
    }

    /** SQL for the bucket key of a date column. */
    public function bucketSql(string $column): string
    {
        return $this->bucket === 'month' ? self::monthSql($column) : "DATE({$column})";
    }

    public static function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    public static function monthSql(string $column): string
    {
        return self::isSqlite() ? "strftime('%Y-%m', {$column})" : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    /** 0-23 */
    public static function hourSql(string $column): string
    {
        return self::isSqlite() ? "CAST(strftime('%H', {$column}) AS INTEGER)" : "HOUR({$column})";
    }

    /** 0 = Sunday … 6 = Saturday */
    public static function weekdaySql(string $column): string
    {
        return self::isSqlite() ? "CAST(strftime('%w', {$column}) AS INTEGER)" : "(DAYOFWEEK({$column}) - 1)";
    }

    /** Percentage change, or null when there is nothing to compare with. */
    public static function delta(float|int $now, float|int $before): ?float
    {
        return $before > 0 ? round(($now - $before) / $before * 100, 1) : null;
    }

    /** 75th percentile (how Core Web Vitals are judged). */
    public static function p75(array $values): ?float
    {
        $values = array_values(array_filter($values, fn ($v) => $v !== null));
        if (! $values) {
            return null;
        }
        sort($values);

        return (float) $values[(int) floor(0.75 * (count($values) - 1))];
    }
}
