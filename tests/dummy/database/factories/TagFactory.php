<?php

namespace DummyApp\Factories;

use DummyApp\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition()
    {
        return [
            'uuid' => $this->faker->uuid,
            'name' => $this->faker->country,
        ];
    }
}
