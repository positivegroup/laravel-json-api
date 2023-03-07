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
use DummyApp\Post;
use DummyApp\Tag;
use DummyApp\Video;

/**
 * Class PolymorphicHasManyTest
 *
 * Test a JSON API has-many relationship that can hold more than one type
 * of resource.
 *
 * In our dummy app, this is the taggables relationship on our tags resource.
 */
class PolymorphicHasManyTest extends TestCase
{
    /**
     * @var string
     */
    protected $resourceType = 'tags';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsUser('admin', 'author');
    }

    public function testCreateWithEmpty()
    {
        $tag = Tag::factory()->make();

        $data = [
            'type' => 'tags',
            'attributes' => [
                'name' => $tag->name,
            ],
            'relationships' => [
                'taggables' => [
                    'data' => [],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']);

        $id = $this->doCreate($data)->assertCreatedWithId($expected);

        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $id,
        ]);
    }

    public function testCreateWithRelated()
    {
        $tag = Tag::factory()->make();
        $post = Post::factory()->create();
        $videos = Video::factory()->times(2)->create();

        $data = [
            'type' => 'tags',
            'attributes' => [
                'name' => $tag->name,
            ],
            'relationships' => [
                'taggables' => [
                    'data' => [
                        [
                            'type' => 'videos',
                            'id' => (string) $videos->first()->getKey(),
                        ],
                        [
                            'type' => 'posts',
                            'id' => (string) $post->getKey(),
                        ],
                        [
                            'type' => 'videos',
                            'id' => (string) $videos->last()->getKey(),
                        ],
                    ],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']);

        $id = $this->doCreate($data)->assertCreatedWithId($expected);
        $tag = Tag::findUuid($id);

        $this->assertTaggablesAre($tag, [$post], $videos);
    }

    public function testUpdateReplacesRelationshipWithEmptyRelationship()
    {
        /** @var Tag $tag */
        $tag = Tag::factory()->create();
        $tag->posts()->saveMany(Post::factory()->times(2)->create());
        $tag->videos()->save(Video::factory()->create());

        $data = [
            'type' => 'tags',
            'id' => $tag->uuid,
            'relationships' => [
                'taggables' => [
                    'data' => [],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']);

        $this->doUpdate($data)->assertUpdated($expected);

        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $tag->getKey(),
        ]);
    }

    public function testUpdateReplacesEmptyRelationshipWithResource()
    {
        $tag = Tag::factory()->create();
        $video = Video::factory()->create();

        $data = [
            'type' => 'tags',
            'id' => $tag->uuid,
            'relationships' => [
                'taggables' => [
                    'data' => [
                        [
                            'type' => 'videos',
                            'id' => (string) $video->getKey(),
                        ],
                    ],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']);

        $this->doUpdate($data)->assertUpdated($expected);

        $this->assertTaggablesAre($tag, [], [$video]);
    }

    public function testUpdateReplacesEmptyRelationshipWithResources()
    {
        $tag = Tag::factory()->create();
        $post = Post::factory()->create();
        $video = Video::factory()->create();

        $data = [
            'type' => 'tags',
            'id' => $tag->uuid,
            'relationships' => [
                'taggables' => [
                    'data' => [
                        [
                            'type' => 'posts',
                            'id' => (string) $post->getKey(),
                        ],
                        [
                            'type' => 'videos',
                            'id' => (string) $video->getKey(),
                        ],
                    ],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']);

        $this->doUpdate($data)->assertUpdated($expected);

        $this->assertTaggablesAre($tag, [$post], [$video]);
    }

    public function testReadRelated()
    {
        /** @var Tag $tag */
        $tag = Tag::factory()->create();
        $tag->posts()->sync($post = Post::factory()->create());
        $tag->videos()->sync($videos = Video::factory()->times(2)->create());

        $this->doReadRelated($tag->uuid, 'taggables')->assertFetchedMany([
            ['type' => 'posts', 'id' => $post],
            ['type' => 'videos', 'id' => $videos[0]],
            ['type' => 'videos', 'id' => $videos[1]],
        ]);
    }

    public function testReadEmptyRelated()
    {
        $tag = Tag::factory()->create();

        $this->doReadRelated($tag->uuid, 'taggables')->assertFetchedNone();
    }

    public function testReadRelationship()
    {
        /** @var Tag $tag */
        $tag = Tag::factory()->create();
        $tag->posts()->sync($post = Post::factory()->create());
        $tag->videos()->sync($videos = Video::factory()->times(2)->create());

        $this->doReadRelationship($tag->uuid, 'taggables')->assertFetchedToMany([
            ['type' => 'posts', 'id' => $post],
            ['type' => 'videos', 'id' => $videos[0]],
            ['type' => 'videos', 'id' => $videos[1]],
        ]);
    }

    public function testReadEmptyRelationship()
    {
        $tag = Tag::factory()->create();

        $this->doReadRelationship($tag->uuid, 'taggables')->assertFetchedNone();
    }

    public function testReplaceEmptyRelationshipWithRelatedResources()
    {
        $tag = Tag::factory()->create();
        $post = Post::factory()->create();
        $video = Video::factory()->create();

        $this->doReplaceRelationship($tag->uuid, 'taggables', [
            [
                'type' => 'videos',
                'id' => (string) $video->getKey(),
            ],
            [
                'type' => 'posts',
                'id' => (string) $post->getKey(),
            ],
        ])->assertStatus(204);

        $this->assertTaggablesAre($tag, [$post], [$video]);
    }

    public function testReplaceRelationshipWithNone()
    {
        /** @var Tag $tag */
        $tag = Tag::factory()->create();
        $tag->videos()->attach(Video::factory()->create());

        $this->doReplaceRelationship($tag->uuid, 'taggables', [])
            ->assertStatus(204);

        $this->assertNoTaggables($tag);
    }

    public function testReplaceRelationshipWithDifferentResources()
    {
        /** @var Tag $tag */
        $tag = Tag::factory()->create();
        $tag->posts()->attach(Post::factory()->create());
        $tag->videos()->attach(Video::factory()->create());

        $posts = Post::factory()->times(2)->create();
        $video = Video::factory()->create();

        $this->doReplaceRelationship($tag->uuid, 'taggables', [
            [
                'type' => 'posts',
                'id' => (string) $posts->last()->getKey(),
            ],
            [
                'type' => 'posts',
                'id' => (string) $posts->first()->getKey(),
            ],
            [
                'type' => 'videos',
                'id' => (string) $video->getKey(),
            ],
        ])->assertStatus(204);

        $this->assertTaggablesAre($tag, $posts, [$video]);
    }

    public function testAddToRelationship()
    {
        /** @var Tag $tag */
        $tag = Tag::factory()->create();
        $tag->posts()->attach($existingPost = Post::factory()->create());
        $tag->videos()->attach($existingVideo = Video::factory()->create());

        $posts = Post::factory()->times(2)->create();
        $video = Video::factory()->create();

        $this->doAddToRelationship($tag->uuid, 'taggables', [
            [
                'type' => 'posts',
                'id' => (string) $posts->last()->getKey(),
            ],
            [
                'type' => 'posts',
                'id' => (string) $posts->first()->getKey(),
            ],
            [
                'type' => 'videos',
                'id' => (string) $video->getKey(),
            ],
        ])->assertStatus(204);

        $this->assertTaggablesAre($tag, $posts->push($existingPost), [$existingVideo, $video]);
    }

    public function testRemoveFromRelationship()
    {
        /** @var Tag $tag */
        $tag = Tag::factory()->create();
        $tag->posts()->saveMany($allPosts = Post::factory()->times(3)->create());
        $tag->videos()->saveMany($allVideos = Video::factory()->times(3)->create());

        /** @var Post $post1 */
        $post1 = $allPosts->first();
        /** @var Post $post2 */
        $post2 = $allPosts->last();
        /** @var Video $video */
        $video = $allVideos->last();

        $this->doRemoveFromRelationship($tag->uuid, 'taggables', [
            [
                'type' => 'posts',
                'id' => (string) $post1->getKey(),
            ],
            [
                'type' => 'posts',
                'id' => (string) $post2->getKey(),
            ],
            [
                'type' => 'videos',
                'id' => (string) $video->getKey(),
            ],
        ])->assertStatus(204);

        $this->assertTaggablesAre(
            $tag,
            [$allPosts->get(1)],
            [$allVideos->first(), $allVideos->get(1)]
        );
    }

    /**
     * @param  Tag  $tag
     * @return void
     */
    private function assertNoTaggables(Tag $tag)
    {
        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $tag->getKey(),
        ]);
    }

    /**
     * @param  Tag  $tag
     * @param  iterable  $posts
     * @param  iterable  $videos
     * @return void
     */
    private function assertTaggablesAre(Tag $tag, $posts, $videos)
    {
        $this->assertSame(
            count($posts) + count($videos),
            \DB::table('taggables')->where('tag_id', $tag->getKey())->count(),
            'Unexpected number of taggables.'
        );

        $this->assertSame(count($posts), $tag->posts()->count(), 'Unexpected number of posts.');
        $this->assertSame(count($videos), $tag->videos()->count(), 'Unexpected number of videos.');

        /** @var Post $post */
        foreach ($posts as $post) {
            $this->assertDatabaseHas('taggables', [
                'taggable_type' => Post::class,
                'taggable_id' => $post->getKey(),
                'tag_id' => $tag->getKey(),
            ]);
        }

        /** @var Video $video */
        foreach ($videos as $video) {
            $this->assertDatabaseHas('taggables', [
                'taggable_type' => Video::class,
                'taggable_id' => $video->getKey(),
                'tag_id' => $tag->getKey(),
            ]);
        }
    }
}
