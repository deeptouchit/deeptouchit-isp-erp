<?php

namespace Database\Factories;

use App\Models\ServerGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ServerGroupFactory extends Factory
{
    protected $model = ServerGroup::class;

    public function definition(): array
    {
        $name = $this->faker->city() . ' Cluster';
        return [
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(100, 999),
            'description' => 'Primary infrastructure cluster in ' . $this->faker->country(),
            'location' => $this->faker->country(),
            'status' => 'active',
        ];
    }
}
