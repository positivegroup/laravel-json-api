<?php

namespace DummyApp\Factories;

use DummyApp\Phone;
use DummyApp\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhoneFactory extends Factory
{
    protected $model = Phone::class;

    public function definition()
    {
        return [
            'number' => $this->faker->numerify('+447#########'),
        ];
    }

    public function user()
    {
        return $this->state(fn () => [
            'user_id' => fn () => User::factory()->create()->getKey(),
        ]);
    }
}
