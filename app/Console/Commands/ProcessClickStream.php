<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Stevebauman\Location\Facades\Location;
use Illuminate\Support\Str;

class ProcessClickStream extends Command
{
    protected $signature = 'shorturl:process-clicks';
    protected $description = 'Process click events from Redis Stream';

    private const STREAM = 'shorturl:clicks';
    private const GROUP = 'click-workers';

    private string $consumer;

    public function __construct() {
        parent::__construct();
        $this->consumer = 'worker-' . gethostname() . '-' . uniqid();
    }

    public function handle()
    {
        $this->info("Worker iniciado: {$this->consumer}");
        $this->ensureStreamAndGroup();
        while (true) {
            try {
                $events = Redis::xreadgroup(
                    self::GROUP,
                    $this->consumer,
                    [self::STREAM => '>'],
                    500,
                    2000
                );
                if ($events === false) {
                    $client = Redis::connection()->client();
                    $error = $client->getLastError();
                    if ($error) {
                        Log::error("Redis Error no XREADGROUP: " . $error);
                        $client->clearLastError();
                        if (str_contains($error, 'NOGROUP')) {
                            $this->ensureStreamAndGroup();
                        }
                    }
                    sleep(1);
                    continue;
                }
                if (empty($events) || empty($events[self::STREAM])) {
                    usleep(300000);
                    continue;
                }
                $this->processEvents($events[self::STREAM]);
            } catch (\Throwable $e) {
                Log::error("Worker crashou: " . $e->getMessage());
                sleep(1);
            }
        }
    }

    private function ensureStreamAndGroup(): void
    {
        Log::info('Garantindo stream e group...');
        $client = Redis::connection()->client();
        $created = $client->xGroup('CREATE', self::STREAM, self::GROUP, '0', true);
        if (!$created) {
            $error = $client->getLastError();
            if ($error && str_contains($error, 'BUSYGROUP')) {
                $client->clearLastError();
                return;
            }
            Log::error('Erro ao criar group: ' . $error);
            throw new \RuntimeException('Falha ao criar o Consumer Group: ' . $error);
        }
        Log::info('Group criado com sucesso');
    }

    private function processEvents(array $events): void
    {
        $counts = [];
        $ids = [];
        $inserts = [];
        $normalizedEvents = [];
        foreach ($events as $id => $fieldsRaw) {
            $fields = (array_keys($fieldsRaw) !== range(0, count($fieldsRaw) - 1))
                ? $fieldsRaw
                : $this->unflattenFields($fieldsRaw);

            $code = $fields['code'] ?? null;
            if (!$code) continue;
            $normalizedEvents[$id] = $fields;
            $counts[$code] = ($counts[$code] ?? 0) + 1;
            $ids[] = $id;
        }
        if (empty($normalizedEvents)) return;
        $codes = array_unique(array_column($normalizedEvents, 'code'));
        $codeToIdMap = DB::table('short_urls')
            ->whereIn('short_code', $codes)
            ->pluck('id', 'short_code')
            ->toArray();
        foreach ($normalizedEvents as $id => $fields) {
            $code = $fields['code'];
            $ip = $fields['ip'] ?? null;
            $shortUrlId = $codeToIdMap[$code] ?? null;
            if ($ip) {
                $this->handleGeoLocation($code, $ip);
            }
            if ($shortUrlId) {
                $inserts[] = [
                    'id' => (string)Str::ulid(),
                    'short_url_id' => $shortUrlId,
                    'ip' => $ip,
                    'user_agent' => $fields['user_agent'] ?? null,
                    'referer' => $fields['referer'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        if ($counts) $this->persistCounts($counts);
        if ($inserts) DB::table('clicks')->insert($inserts);
        if ($ids) Redis::xack(self::STREAM, self::GROUP, $ids);
    }
    private function handleGeoLocation(string $code, string $ip): void
    {
        try {
            $position = Location::get($ip);
            if ($position) {
                Redis::geoadd("shorturl:geo:{$code}", $position->longitude, $position->latitude, uniqid());
            }
        } catch (\Throwable $e) {
            Log::warning("Erro GeoIP: " . $e->getMessage());
        }
    }
    private function persistCounts(array $counts): void
    {
        $values = [];
        $bindings = [];
        foreach ($counts as $code => $count) {
            $values[] = "(?, ?::int)";
            $bindings[] = $code;
            $bindings[] = $count;
        }
        $sql = "
            UPDATE short_urls s
            SET clicks = s.clicks + v.count
            FROM (
                VALUES " . implode(',', $values) . "
            ) AS v(short_code, count)
            WHERE s.short_code = v.short_code
        ";
        DB::update($sql, $bindings);
        foreach (array_keys($counts) as $code) {
            Redis::del("shorturl:{$code}");
        }
    }
}
