<?php

namespace DummyApp\Factories;

use DummyApp\History;
use DummyApp\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HistoryFactory extends Factory
{
    protected $model = History::class;

    public function definition()
    {
        return [
            'detail' => $this->faker->paragraph,
            'user_id' => fn () => User::factory()->create()->getKey(),
        ];
    }
}
