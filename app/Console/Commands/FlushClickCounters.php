<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class FlushClickCounters extends Command
{
    protected $signature = 'shorturl:flush-clicks';
    protected $description = 'Flush Redis click counters to database';

    private const PATTERN = 'shorturl:clicks:*';

    public function handle()
    {
        while (true) {
            $cursor = 0;
            $counts = [];
            do {
                [$cursor, $keys] = Redis::scan(
                    $cursor,
                    'MATCH',
                    self::PATTERN,
                    'COUNT',
                    100
                );
                if (!$keys) {
                    continue;
                }
                foreach ($keys as $key) {
                    $code = str_replace('shorturl:clicks:', '', $key);
                    $count = Redis::getset($key, 0);
                    if ($count > 0) {
                        $counts[$code] = ($counts[$code] ?? 0) + $count;
                    }
                }
                if (count($counts) >= 500) {
                    $this->persistCounts($counts);
                    $counts = [];
                }
            } while ($cursor != 0);
            if ($counts) {
                $this->persistCounts($counts);
            }
            sleep(5);
        }
    }

    private function persistCounts(array $counts): void
    {
        if (!$counts) {
            return;
        }
        $values = [];
        $bindings = [];
        foreach ($counts as $code => $count) {
            $values[] = "(?, ?)";
            $bindings[] = $code;
            $bindings[] = $count;
        }
        $sql = "
            UPDATE short_urls s
            SET clicks = s.clicks + v.count
            FROM (
                VALUES ".implode(',', $values)."
            ) AS v(short_code, count)
            WHERE s.short_code = v.short_code
        ";
        DB::update($sql, $bindings);
    }
}
