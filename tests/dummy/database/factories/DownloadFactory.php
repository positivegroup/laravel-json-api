<?php

namespace DummyApp\Factories;

use DummyApp\Download;
use Illuminate\Database\Eloquent\Factories\Factory;

class DownloadFactory extends Factory
{
    protected $model = Download::class;

    public function definition()
    {
        return [
            'category' => $this->faker->randomElement([
                'my-posts',
                'my-comments',
                'my-videos',
            ]),
        ];
    }
}
