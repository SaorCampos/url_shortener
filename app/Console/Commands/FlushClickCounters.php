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
        $this->info("Iniciando limpeza de contadores (threshold: {$days} dias)...");
        $this->cleanKeysByPattern('shorturl:clicks:minute:*', 'YmdHi', $days);
        $this->cleanKeysByPattern('shorturl:top:*', 'YmdHi', $days);
        $this->cleanKeysByPattern('shorturl:country:*:*', 'Ymd', $days);
        $this->info("Processo de limpeza finalizado.");
    }

    private function cleanKeysByPattern(string $pattern, string $dateFormat, int $days)
    {
        $countDeleted = 0;
        $threshold = now()->subDays($days)->timestamp;
        $keys = Redis::keys($pattern);
        if (empty($keys)) {
            $this->info("Padrão [{$pattern}]: Nenhuma chave encontrada.");
            return;
        }
        $prefix = config('database.redis.options.prefix', '');
        foreach ($keys as $rawKey) {
            $cleanKey = $prefix ? preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $rawKey) : $rawKey;
            $parts = explode(':', $cleanKey);
            $dateStr = end($parts);
            try {
                $keyDate = Carbon::createFromFormat($dateFormat, $dateStr);
                if ($keyDate && $keyDate->timestamp < $threshold) {
                    Redis::del($cleanKey);
                    $countDeleted++;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
        $this->info("Padrão [{$pattern}]: {$countDeleted} chaves removidas.");
    }
}
