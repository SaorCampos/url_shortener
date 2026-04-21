<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Throwable;

class FlushClickCounters extends Command
{
    protected $signature = 'shorturl:flush-clicks {--days=7 : wipes cached click counters older than specified days}';
    protected $description = 'Wipe cached click counters from Redis to free up memory.';

    public function handle()
    {
        $days = (int) $this->option('days');
        $this->info("Iniciando limpeza de contadores com mais de {$days} dias...");
        $this->cleanUpMinuteClicks($days);
        $this->cleanupTopMinutes($days);
        $this->cleanUpGeoHeatMap($days);
        $this->info("Processo de limpeza finalizado.");
    }

    private function cleanUpMinuteClicks(int $days)
    {
        $cursor = "0";
        $countDeleted = 0;
        $threshold = now()->subDays($days);
        do {
            [$cursor, $keys] = Redis::scan($cursor, [
                'MATCH' => 'shorturl:clicks:minute:*',
                'COUNT' => 1000
            ]);
            if (!empty($keys)) {
                foreach ($keys as $key) {
                    $parts = explode(':', $key);
                    $dateStr = end($parts); // YmdHi
                    try {
                        $keyDate = Carbon::createFromFormat('YmdHi', $dateStr);
                        if ($keyDate->lessThan($threshold)) {
                            Redis::del($key);
                            $countDeleted++;
                        }
                    } catch (Throwable $e) {
                        continue;
                    }
                }
            }
        } while ($cursor !== "0");
        $this->info("Removidas {$countDeleted} chaves de cliques por minuto.");
    }
    private function cleanupTopMinutes(int $days)
    {
        $cursor = "0";
        $count = 0;
        $threshold = now()->subDays($days);
        do {
            [$cursor, $keys] = Redis::scan($cursor, [
                'MATCH' => 'shorturl:top:*',
                'COUNT' => 1000
            ]);
            foreach ($keys as $key) {
                $parts = explode(':', $key);
                $dateStr = end($parts);
                try {
                    $keyDate = Carbon::createFromFormat('YmdHi', $dateStr);
                    if ($keyDate->lessThan($threshold)) {
                        Redis::del($key);
                        $count++;
                    }
                } catch (Throwable $e) {}
            }
        } while ($cursor !== "0");
        $this->info("Removidas {$count} chaves de top rankings.");
    }
    private function cleanUpGeoHeatMap(int $days)
    {
        $cursor = "0";
        $count = 0;
        $threshold = now()->subDays($days);
        do {
            [$cursor, $keys] = Redis::scan($cursor, [
                'MATCH' => 'shorturl:country:*:*',
                'COUNT' => 1000
            ]);
            foreach ($keys as $key) {
                $parts = explode(':', $key);
                $dateStr = end($parts); // Ymd
                try {
                    $keyDate = Carbon::createFromFormat('Ymd', $dateStr);
                    if ($keyDate->lessThan($threshold)) {
                        Redis::del($key);
                        $count++;
                    }
                } catch (Throwable $e) {}
            }
        } while ($cursor !== "0");
        $this->info("Removidas {$count} chaves de estatísticas geográficas.");
    }
}
