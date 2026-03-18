<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessClickStream extends Command
{
    protected $signature = 'shorturl:process-clicks';
    protected $description = 'Process click events from Redis Stream';

    private const STREAM = 'shorturl:clicks';
    private const GROUP = 'click-workers';
    private const CONSUMER = 'worker-1';

    public function handle()
    {
        $this->ensureGroup();
        while (true) {
            $this->recoverPending();
            $events = Redis::xreadgroup(
                self::GROUP,
                self::CONSUMER,
                [self::STREAM => '>'],
                500,
                2000
            );
            if (!$events) {
                usleep(300000);
                continue;
            }
            $this->processEvents($events[self::STREAM]);
        }
    }

    private function processEvents(array $events): void
    {
        $counts = [];
        $ids = [];
        foreach ($events as $id => $fields) {
            $code = $fields['code'];
            $counts[$code] = ($counts[$code] ?? 0) + 1;
            $ids[] = $id;
        }
        $this->persistCounts($counts);
        Redis::xack(self::STREAM, self::GROUP, $ids);
    }
    private function persistCounts(array $counts): void
    {
        if (empty($counts)) {
            return;
        }
        $values = [];
        $bindings = [];
        foreach ($counts as $code => $count) {
            $values[] = "(?, ?::int)";
            $bindings[] = $code;
            $bindings[] = (int) $count;
        }
        $sql = "
        UPDATE short_urls s
        SET clicks = s.clicks + v.count::int
        FROM (
            VALUES " . implode(',', $values) . "
        ) AS v(short_code, count)
        WHERE s.short_code = v.short_code
    ";
        DB::update($sql, $bindings);
    }
    private function ensureGroup(): void
    {
        try {
            Redis::xgroup(
                'CREATE',
                self::STREAM,
                self::GROUP,
                '0',
                true
            );
        } catch (\Exception $e) {
            Log::warning("Group creation failed: " . $e->getMessage());
        }
    }

    private function recoverPending(): void
    {
        try {
            $result = Redis::xautoclaim(
                self::STREAM,
                self::GROUP,
                self::CONSUMER,
                60000,
                '0-0',
                100
            );
            if (!empty($result[1])) {
                $this->processEvents($result[1]);
            }
        } catch (\Exception $e) {
            Log::warning("XAUTOCLAIM failed: " . $e->getMessage());
        }
    }
}
