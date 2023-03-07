<?php

namespace Database\Factories;

use CloudCreativity\LaravelJsonApi\Queue\ClientJob;
use DummyApp\Download;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientJobFactory extends Factory
{
    protected $model = ClientJob::class;

    public function definition()
    {
        return [
            'api' => 'v1',
            'failed' => false,
            'resource_type' => 'downloads',
            'attempts' => 0,
        ];
    }

    public function success()
    {
        return $this->state(fn () => [
            'completed_at' => $this->faker->dateTimeBetween('-10 minutes', 'now'),
            'failed' => false,
            'attempts' => $this->faker->numberBetween(1, 3),
        ]);
    }

    public function failed()
    {
        return $this->state(fn () => [
            'completed_at' => $this->faker->dateTimeBetween('-10 minutes', 'now'),
            'failed' => true,
            'attempts' => $this->faker->numberBetween(1, 3),
        ]);
    }

    public function withDownload()
    {
        return $this->state(fn () => [
            'resource_type' => 'downloads',
            'resource_id' => Download::factory()->create()->getRouteKey(),
        ]);
    }
}
