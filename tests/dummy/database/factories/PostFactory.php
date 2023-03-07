<?php

namespace DummyApp\Factories;

use DummyApp\Post;
use DummyApp\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'slug' => $this->faker->unique()->slug,
            'content' => $this->faker->text,
            'author_id' => fn () => User::factory()->author()->create()->getKey(),
        ];
    }

    public function published()
    {
        return $this->state(fn () => [
            'published_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}
