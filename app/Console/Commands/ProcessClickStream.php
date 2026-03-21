<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Stevebauman\Location\Facades\Location;

class ProcessClickStream extends Command
{
    protected $signature = 'shorturl:process-clicks';
    protected $description = 'Process click events from Redis Stream';

    private const STREAM = 'shorturl:clicks';
    private const GROUP = 'click-workers';

    private string $consumer;

    public function __construct()
    {
        parent::__construct();
        $this->consumer = 'worker-' . gethostname() . '-' . uniqid();
    }

    public function handle()
    {
        $this->info("Worker iniciado: {$this->consumer}");
        // Log::info("Worker iniciado: {$this->consumer}");
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
                            // Log::info("Tentando recriar o grupo...");
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
                Log::info('Eventos recebidos', ['count' => count($events[self::STREAM])]);
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
                // Log::info('Group já existe');
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
        // Log::info('Processando eventos', ['count' => count($events)]);
        $counts = [];
        $ids = [];
        foreach ($events as $id => $fieldsRaw) {
            if (array_keys($fieldsRaw) !== range(0, count($fieldsRaw) - 1)) {
                $fields = $fieldsRaw;
            } else {
                $fields = [];
                for ($i = 0; $i < count($fieldsRaw); $i += 2) {
                    if (!isset($fieldsRaw[$i + 1])) continue;
                    $fields[$fieldsRaw[$i]] = $fieldsRaw[$i + 1];
                }
            }
            // Log::info('Processando evento', ['id' => $id, 'fields' => $fields]);
            $code = $fields['code'] ?? null;
            if (!$code) {
                Log::warning('Evento inválido', $fields);
                continue;
            }
            $ip = $fields['ip'] ?? null;
            $counts[$code] = ($counts[$code] ?? 0) + 1;
            if ($ip) {
                try {
                    $position = Location::get($ip);
                    if ($position && $position !== false) {
                        Redis::geoadd(
                            "shorturl:geo:{$code}",
                            $position->longitude,
                            $position->latitude,
                            uniqid()
                        );
                    }
                } catch (\Throwable $e) {
                    Log::warning('Erro no geo IP: ' . $e->getMessage());
                }
            }
            $ids[] = $id;
        }
        if ($counts) {
            Log::info('Persistindo clicks', $counts);
            $this->persistCounts($counts);
        }
        if ($ids) {
            Redis::xack(self::STREAM, self::GROUP, $ids);
            // Log::info('Eventos confirmados (ACK)', ['count' => count($ids)]);
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
        $updated = DB::update($sql, $bindings);
        // Log::info('Rows afetadas', ['count' => $updated]);
    }
}
