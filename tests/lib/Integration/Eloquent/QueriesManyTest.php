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

class QueriesManyTest extends TestCase
{
    /**
     * @var string
     */
    protected $resourceType = 'posts';

    public function testRelated()
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $expected = collect([
            Post::factory()->create(['author_id' => $post->getKey()]),
            /** @var Post $tagged */
            $tagged = Post::factory()->create(),
        ]);

        $tag = $post->tags()->create(['name' => 'jsonapi']);
        $tagged->tags()->sync($tag);

        Post::factory()->times(3)->create();

        $this->doReadRelated($post, 'related')
            ->assertReadHasMany('posts', $expected);
    }

    public function testRelationship()
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $expected = collect([
            Post::factory()->create(['author_id' => $post->getKey()]),
            /** @var Post $tagged */
            $tagged = Post::factory()->create(),
        ]);

        $tag = $post->tags()->create(['name' => 'jsonapi']);
        $tagged->tags()->sync($tag);

        Post::factory()->times(3)->create();

        $this->doReadRelationship($post, 'related')
            ->assertReadHasManyIdentifiers('posts', $expected);
    }
}
