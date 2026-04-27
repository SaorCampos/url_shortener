<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\ShortUrlModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Infrastructure\Persistence\Eloquent\Models\ClickModel;

class ClickFactory extends Factory
{
    protected $model = ClickModel::class;

    public function definition(): array
    {
        return [
            'short_url_id' => ShortUrlModel::factory()->create()->id,
            'ip' => fake()->ipv4(),
            'country_code' => fake()->countryCode(),
            'user_agent' => fake()->userAgent(),
            'referer' => fake()->url(),
            'lat' => fake()->latitude(),
            'lng' => fake()->longitude(),
        ];
    }
}
