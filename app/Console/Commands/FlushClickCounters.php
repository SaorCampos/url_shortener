<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class FlushClickCounters extends Command
{
      #   command: php artisan shorturl:flush-clicks
    protected $signature = 'shorturl:flush-clicks {--days=7 : wipes cached click counters older than specified days}';
    protected $description = 'Wipe cached click counters from Redis to free up memory.';

    public function handle()
    {
        $days = (int) $this->option('days');
        $this->info("Iniciando limpeza de contadores com mais de {$days} dias...");
        $cursor = "0";
        $countDeleted = 0;
        do {
            [$cursor, $keys] = Redis::scan($cursor, [
                'MATCH' => 'shorturl:clicks:minute:*',
                'COUNT' => 1000
            ]);
            if (!empty($keys)) {
                foreach ($keys as $key) {
                    $parts = explode(':', $key);
                    $dateStr = end($parts);
                    try {
                        $keyDate = \DateTimeImmutable::createFromFormat('YmdHi', $dateStr);
                        if ($keyDate && $keyDate < now()->subDays($days)) {
                            Redis::del($key);
                            $countDeleted++;
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        } while ($cursor !== "0");
        $this->cleanupTopMinutes($days);
        $this->cleanUpGeoHeatMap(30);
        $this->info("Limpeza concluída! {$countDeleted} chaves removidas.");
    }

    private function cleanupTopMinutes(int $days)
    {
        $cursor = "0";
        do {
            [$cursor, $keys] = Redis::scan($cursor, [
                'MATCH' => 'shorturl:top:*', // shorturl:top:YmdHi
                'COUNT' => 1000
            ]);
            foreach ($keys as $key) {
                $parts = explode(':', $key);
                $dateStr = end($parts);
                $keyDate = \DateTimeImmutable::createFromFormat('YmdHi', $dateStr);
                if ($keyDate && $keyDate < now()->subDays($days)) {
                    Redis::del($key);
                }
            }
        } while ($cursor !== "0");
    }
    private function cleanUpGeoHeatMap(int $days)
    {
        $cursor = "0";
        $count = 0;
        $this->info("Limpando estatísticas de países com mais de {$days} dias...");
        do {
            [$cursor, $keys] = Redis::scan($cursor, [
                'MATCH' => 'shorturl:country:*:*',
                'COUNT' => 1000
            ]);
            foreach ($keys as $key) {
                $parts = explode(':', $key);
                $dateStr = end($parts);
                if (strlen($dateStr) === 8 && is_numeric($dateStr)) {
                    $keyDate = \DateTimeImmutable::createFromFormat('Ymd', $dateStr);
                    if ($keyDate && $keyDate < now()->subDays($days)) {
                        Redis::del($key);
                        $count++;
                    }
                }
            }
        } while ($cursor !== "0");
        $this->info("Removidas {$count} chaves de países obsoletas.");
    }
}
