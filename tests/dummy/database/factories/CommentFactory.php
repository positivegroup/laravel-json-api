<?php

namespace DummyApp\Factories;

use DummyApp\Comment;
use DummyApp\Post;
use DummyApp\User;
use DummyApp\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition()
    {
        return [
            'content' => $this->faker->paragraph,
            'user_id' => fn () => User::factory()->create()->getKey(),
        ];
    }

    public function post()
    {
        return $this->state(fn () => [
            'commentable_type' => Post::class,
            'commentable_id' => fn () => Post::factory()->published()->create()->getKey(),
        ]);
    }

    public function video()
    {
        return $this->state(fn () => [
            'commentable_type' => Video::class,
            'commentable_id' => fn () => Video::factory()->create()->getKey(),
        ]);
    }
}
