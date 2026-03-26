<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Infrastructure\Persistence\Eloquent\Models\ShortUrlModel;
use App\Infrastructure\Cache\BloomFilterService;

class SyncBloomFilter extends Command
{
    protected $signature = 'shorturl:sync-bloom';
    protected $description = 'Sincroniza todos os short codes do banco com o Bloom Filter';

    public function handle(BloomFilterService $bloomFilter)
    {
        $this->info('Iniciando sincronização...');
            ShortUrlModel::select('short_code')->chunk(1000, function ($urls) use ($bloomFilter) {
            foreach ($urls as $url) {
                $bloomFilter->add($url->getRawOriginal('short_code'));
            }
            $this->comment("Sincronizando lote de 1000...");
        });
        $this->info('Bloom Filter sincronizado com sucesso!');
    }
}
