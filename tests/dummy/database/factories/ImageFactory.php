<?php

namespace DummyApp\Factories;

use DummyApp\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageFactory extends Factory
{
    protected $model = Image::class;

    public function definition()
    {
        return [
            'url' => $this->faker->imageUrl(),
        ];
    }
}
