<?php

namespace DummyApp\Factories;

use DummyApp\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->email,
            'password' => bcrypt(Str::random(10)),
        ];
    }

    public function author()
    {
        return $this->state(fn () => [
            'author' => true,
        ]);
    }

    public function admin()
    {
        return $this->state(fn () => [
            'admin' => true,
        ]);
    }
}
