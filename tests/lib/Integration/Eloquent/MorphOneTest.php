<?php
/**
 * Copyright 2020 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Image;
use DummyApp\Post;

/**
 * Class MorphOneTest
 *
 * Tests a JSON API has-one relationship that relates to an Eloquent morph-one
 * relationship.
 *
 * In our dummy app, this is the image relationship on a posts model.
 */
class MorphOneTest extends TestCase
{
    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->actingAsUser();
    }

    public function testCreateWithNull()
    {
        $post = Post::factory()->make();

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $post->title,
                'content' => $post->content,
                'slug' => $post->slug,
            ],
            'relationships' => [
                'image' => [
                    'data' => null,
                ],
            ],
        ];

        $id = $this
            ->doCreate($data, ['include' => 'image'])
            ->assertCreatedWithId($data);

        $this->assertDatabaseHas('posts', [
            'id' => $id,
            'title' => $data['attributes']['title'],
        ]);
    }

    public function testCreateWithRelated()
    {
        $post = Post::factory()->make();
        $image = Image::factory()->create();

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $post->title,
                'content' => $post->content,
                'slug' => $post->slug,
            ],
            'relationships' => [
                'image' => [
                    'data' => [
                        'type' => 'images',
                        'id' => (string) $image->getRouteKey(),
                    ],
                ],
            ],
        ];

        $id = $this
            ->doCreate($data, ['include' => 'image'])
            ->assertCreatedWithId($data);

        $this->assertDatabaseHas('posts', [
            'id' => $id,
            'title' => $data['attributes']['title'],
        ]);

        $this->assertDatabaseHas('images', [
            $image->getKeyName() => $image->getKey(),
            'imageable_type' => Post::class,
            'imageable_id' => $id,
        ]);
    }

    public function testUpdateReplacesRelationshipWithNull()
    {
        $post = Post::factory()->create();

        /** @var Image $image */
        $image = Image::factory()->make();
        $image->imageable()->associate($post)->save();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'relationships' => [
                'image' => [
                    'data' => null,
                ],
            ],
        ];

        $this->doUpdate($data, ['include' => 'image'])
            ->assertUpdated($data);

        $this->assertDatabaseHas('images', [
            $image->getKeyName() => $image->getKey(),
            'imageable_type' => null,
            'imageable_id' => null,
        ]);
    }

    public function testUpdateReplacesNullRelationshipWithResource()
    {
        $post = Post::factory()->create();

        /** @var Image $image */
        $image = Image::factory()->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'relationships' => [
                'image' => [
                    'data' => [
                        'type' => 'images',
                        'id' => (string) $image->getRouteKey(),
                    ],
                ],
            ],
        ];

        $this->doUpdate($data, ['include' => 'image'])
            ->assertUpdated($data);

        $this->assertDatabaseHas('images', [
            $image->getKeyName() => $image->getKey(),
            'imageable_type' => Post::class,
            'imageable_id' => $post->getKey(),
        ]);
    }

    public function testUpdateChangesRelatedResource()
    {
        $post = Post::factory()->create();

        /** @var Image $existing */
        $existing = Image::factory()->make();
        $existing->imageable()->associate($post)->save();

        /** @var Image $expected */
        $expected = Image::factory()->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'relationships' => [
                'image' => [
                    'data' => [
                        'type' => 'images',
                        'id' => (string) $expected->getRouteKey(),
                    ],
                ],
            ],
        ];

        $this->doUpdate($data, ['include' => 'image'])
            ->assertUpdated($data);

        $this->assertDatabaseHas('images', [
            $expected->getKeyName() => $expected->getKey(),
            'imageable_type' => Post::class,
            'imageable_id' => $post->getKey(),
        ]);

        $this->assertDatabaseHas('images', [
            $existing->getKeyName() => $existing->getKey(),
            'imageable_type' => null,
            'imageable_id' => null,
        ]);
    }

    public function testReadRelated()
    {
        $post = Post::factory()->create();

        /** @var Image $image */
        $image = Image::factory()->make();
        $image->imageable()->associate($post)->save();

        $expected = [
            'type' => 'images',
            'id' => (string) $image->getRouteKey(),
            'attributes' => [
                'url' => $image->url,
            ],
        ];

        $this->doReadRelated($post, 'image')
            ->assertReadHasOne($expected);
    }

    public function testReadRelatedNull()
    {
        $post = Post::factory()->create();

        $this->doReadRelated($post, 'image')
            ->assertReadHasOne(null);
    }

    public function testReadRelationship()
    {
        $post = Post::factory()->create();

        /** @var Image $image */
        $image = Image::factory()->make();
        $image->imageable()->associate($post)->save();

        $this->doReadRelationship($post, 'image')
            ->assertReadHasOneIdentifier('images', $image->getRouteKey());
    }

    public function testReadEmptyRelationship()
    {
        $post = Post::factory()->create();

        $this->doReadRelationship($post, 'image')
            ->assertReadHasOneIdentifier(null);
    }

    public function testReplaceNullRelationshipWithRelatedResource()
    {
        $post = Post::factory()->create();

        /** @var Image $image */
        $image = Image::factory()->create();

        $data = ['type' => 'images', 'id' => (string) $image->getRouteKey()];

        $this->doReplaceRelationship($post, 'image', $data)
            ->assertStatus(204);

        $this->assertDatabaseHas('images', [
            $image->getKeyName() => $image->getKey(),
            'imageable_type' => Post::class,
            'imageable_id' => $post->getKey(),
        ]);
    }

    public function testReplaceRelationshipWithNull()
    {
        $post = Post::factory()->create();

        /** @var Image $image */
        $image = Image::factory()->create();
        $image->imageable()->associate($post)->save();

        $this->doReplaceRelationship($post, 'image', null)
            ->assertStatus(204);

        $this->assertDatabaseHas('images', [
            $image->getKeyName() => $image->getKey(),
            'imageable_type' => null,
            'imageable_id' => null,
        ]);
    }

    public function testReplaceRelationshipWithDifferentResource()
    {
        $post = Post::factory()->create();

        /** @var Image $existing */
        $existing = Image::factory()->make();
        $existing->imageable()->associate($post)->save();

        /** @var Image $image */
        $image = Image::factory()->create();

        $data = ['type' => 'images', 'id' => (string) $image->getRouteKey()];

        $this->doReplaceRelationship($post, 'image', $data)
            ->assertStatus(204);

        $this->assertDatabaseHas('images', [
            $image->getKeyName() => $image->getKey(),
            'imageable_type' => Post::class,
            'imageable_id' => $post->getKey(),
        ]);

        $this->assertDatabaseHas('images', [
            $existing->getKeyName() => $existing->getKey(),
            'imageable_type' => null,
            'imageable_id' => null,
        ]);
    }
}
