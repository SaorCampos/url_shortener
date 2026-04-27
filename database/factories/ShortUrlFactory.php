<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\ShortUrlModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShortUrlFactory extends Factory
{
    protected $model = ShortUrlModel::class;

    public function definition(): array
    {
        return [
            'original_url' => fake()->url(),
            'short_code' => fake()->unique()->lexify('??????'),
            'clicks' => 0,
            'expires_at' => now()->addDays(7)
        ];
    }
}
