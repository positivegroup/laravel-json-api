<?php

namespace DummyApp\Factories;

use DummyApp\User;
use DummyApp\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition()
    {
        return [
            'uuid' => $this->faker->unique()->uuid,
            'url' => $this->faker->url,
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph,
            'user_id' => fn () => User::factory()->create()->getKey(),
        ];
    }
}
