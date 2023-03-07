<?php

namespace DummyApp\Factories;

use DummyApp\Avatar;
use DummyApp\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AvatarFactory extends Factory
{
    protected $model = Avatar::class;

    public function definition()
    {
        return [
            'path' => 'avatars/'.Str::random(6).'.jpg',
            'media_type' => 'image/jpeg',
            'user_id' => fn () => User::factory()->create()->getKey(),
        ];
    }
}
