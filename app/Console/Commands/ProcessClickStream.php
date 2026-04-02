<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Stevebauman\Location\Facades\Location;

class ProcessClickStream extends Command
{
    protected $signature = 'shorturl:process-clicks';
    private const STREAM = 'shorturl:clicks';
    private const GROUP = 'click-workers';

    private array $geoCache = [];
    private string $consumer;

    public function __construct()
    {
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

    private function processEvents(array $events): void
    {
        $ids = [];
        $inserts = [];
        $counts = [];
        $codes = array_unique(array_column($events, 'code'));
        $codeToIdMap = DB::table('short_urls')
            ->whereIn('short_code', $codes)
            ->pluck('id', 'short_code')
            ->toArray();
        foreach ($events as $id => $fields) {
            $code = $fields['code'];
            $ip = $fields['ip'];
            $ts = (int) $fields['ts'];
            $date = date('Ymd', $ts);
            $hour = date('H', $ts);
            $geo = $this->getLocation($ip);
            if ($geo['country']) {
                Redis::pipeline(function ($pipe) use ($code, $geo, $date, $hour) {
                    $pipe->hincrby("shorturl:country:{$code}:{$date}", $geo['country'], 1);
                    $pipe->hincrby("shorturl:heatmap:{$code}", $hour, 1);
                    if ($geo['lat']) {
                        $pipe->geoadd("shorturl:geo:{$code}", $geo['lng'], $geo['lat'], (string)Str::ulid());
                    }
                });
            }
            if (isset($codeToIdMap[$code])) {
                $inserts[] = [
                    'id' => (string)Str::ulid(),
                    'short_url_id' => $codeToIdMap[$code],
                    'ip' => $ip,
                    'country_code' => $geo['country'],
                    'lat' => $geo['lat'],
                    'lng' => $geo['lng'],
                    'user_agent' => $fields['ua'] ?? null,
                    'referer' => $fields['ref'] ?? null,
                    'created_at' => date('Y-m-d H:i:s', $ts),
                    'updated_at' => date('Y-m-d H:i:s', $ts),
                ];
                $counts[$code] = ($counts[$code] ?? 0) + 1;
            }
            $ids[] = $id;
        }
        if ($inserts) DB::table('clicks')->insert($inserts);
        if ($counts) $this->syncClickCounts($counts);
        Redis::xack(self::STREAM, self::GROUP, $ids);
    }
    private function getLocation(string $ip): array
    {
        if (isset($this->geoCache[$ip])) return $this->geoCache[$ip];

        try {
            $pos = Location::get($ip);
            $data = [
                'country' => $pos ? $pos->countryCode : null,
                'lat' => $pos ? $pos->latitude : null,
                'lng' => $pos ? $pos->longitude : null,
            ];
        } catch (\Throwable $e) {
            $data = ['country' => null, 'lat' => null, 'lng' => null];
        }
        $this->geoCache[$ip] = $data;
        return $data;
    }
    private function syncClickCounts(array $counts): void
    {
        foreach ($counts as $code => $count) {
            DB::table('short_urls')->where('short_code', $code)->increment('clicks', $count);
            Redis::del("shorturl:{$code}");
        }
    }
    private function ensureStreamAndGroup(): void
    {
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
}
