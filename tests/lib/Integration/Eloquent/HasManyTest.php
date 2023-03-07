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
use DummyApp\Country;
use DummyApp\Phone;
use DummyApp\User;

/**
 * Class HasManyTest
 *
 * Test a JSON API has-many relationship that relates to an Eloquent
 * has-many relationship.
 *
 * In our dummy app, this is the users relationship on a country model.
 */
class HasManyTest extends TestCase
{
    /**
     * @var string
     */
    protected $resourceType = 'countries';

    public function testCreateWithEmpty()
    {
        /** @var Country $country */
        $country = Country::factory()->make();

        $data = [
            'type' => 'countries',
            'attributes' => [
                'name' => $country->name,
                'code' => $country->code,
            ],
            'relationships' => [
                'users' => [
                    'data' => [],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']);

        $id = $this->doCreate($data)->assertCreatedWithId($expected);

        $this->assertDatabaseMissing('users', [
            'country_id' => $id,
        ]);
    }

    public function testCreateWithRelated()
    {
        /** @var Country $country */
        $country = Country::factory()->make();
        $user = User::factory()->create();

        $data = [
            'type' => 'countries',
            'attributes' => [
                'name' => $country->name,
                'code' => $country->code,
            ],
            'relationships' => [
                'users' => [
                    'data' => [
                        [
                            'type' => 'users',
                            'id' => (string) $user->getRouteKey(),
                        ],
                    ],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']);

        $id = $this
            ->doCreate($data)
            ->assertCreatedWithId($expected);

        $this->assertUserIs(Country::find($id), $user);
    }

    public function testCreateWithManyRelated()
    {
        /** @var Country $country */
        $country = Country::factory()->make();
        $users = User::factory()->times(2)->create();

        $data = [
            'type' => 'countries',
            'attributes' => [
                'name' => $country->name,
                'code' => $country->code,
            ],
            'relationships' => [
                'users' => [
                    'data' => [
                        [
                            'type' => 'users',
                            'id' => (string) $users->first()->getRouteKey(),
                        ],
                        [
                            'type' => 'users',
                            'id' => (string) $users->last()->getRouteKey(),
                        ],
                    ],
                ],
            ],
        ];

        $id = $this->doCreate($data)->assertCreatedWithId(
            collect($data)->forget('relationships')->all()
        );

        $this->assertUsersAre(Country::find($id), $users);
    }

    public function testUpdateReplacesRelationshipWithEmptyRelationship()
    {
        /** @var Country $country */
        $country = Country::factory()->create();
        $users = User::factory()->times(2)->create();
        $country->users()->saveMany($users);

        $data = [
            'type' => 'countries',
            'id' => (string) $country->getRouteKey(),
            'relationships' => [
                'users' => [
                    'data' => [],
                ],
            ],
        ];

        $this->doUpdate($data)->assertUpdated(
            collect($data)->forget('relationships')->all()
        );

        $this->assertDatabaseMissing('users', [
            'country_id' => $country->getKey(),
        ]);
    }

    public function testUpdateReplacesEmptyRelationshipWithResource()
    {
        /** @var Country $country */
        $country = Country::factory()->create();
        $user = User::factory()->create();

        $data = [
            'type' => 'countries',
            'id' => (string) $country->getRouteKey(),
            'relationships' => [
                'users' => [
                    'data' => [
                        [
                            'type' => 'users',
                            'id' => (string) $user->getRouteKey(),
                        ],
                    ],
                ],
            ],
        ];

        $this->doUpdate($data)->assertUpdated(
            collect($data)->forget('relationships')->all()
        );
        $this->assertUserIs($country, $user);
    }

    public function testUpdateChangesRelatedResources()
    {
        /** @var Country $country */
        $country = Country::factory()->create();
        $country->users()->saveMany(User::factory()->times(3)->create());

        $users = User::factory()->times(2)->create();

        $data = [
            'type' => 'countries',
            'id' => (string) $country->getRouteKey(),
            'relationships' => [
                'users' => [
                    'data' => [
                        [
                            'type' => 'users',
                            'id' => (string) $users->first()->getRouteKey(),
                        ],
                        [
                            'type' => 'users',
                            'id' => (string) $users->last()->getRouteKey(),
                        ],
                    ],
                ],
            ],
        ];

        $this->doUpdate($data)->assertUpdated(
            collect($data)->forget('relationships')->all()
        );
        $this->assertUsersAre($country, $users);
    }

    public function testReadRelated()
    {
        /** @var Country $country */
        $country = Country::factory()->create();
        $users = User::factory()->times(2)->create();

        $country->users()->saveMany($users);

        $this->doReadRelated($country, 'users')
            ->assertReadHasMany('users', $users);
    }

    public function testReadRelatedEmpty()
    {
        /** @var Country $country */
        $country = Country::factory()->create();

        $this->doReadRelated($country, 'users')
            ->assertReadHasMany(null);
    }

    public function testReadRelatedWithFilter()
    {
        $country = Country::factory()->create();

        $a = User::factory()->create([
            'name' => 'John Doe',
            'country_id' => $country->getKey(),
        ]);

        $b = User::factory()->create([
            'name' => 'Jane Doe',
            'country_id' => $country->getKey(),
        ]);

        User::factory()->create([
            'name' => 'Frankie Manning',
            'country_id' => $country->getKey(),
        ]);

        $this->doReadRelated($country, 'users', ['filter' => ['name' => 'Doe']])
            ->assertReadHasMany('users', [$a, $b]);
    }

    public function testReadRelatedWithInvalidFilter()
    {
        $country = Country::factory()->create();

        $this->doReadRelated($country, 'users', ['filter' => ['name' => '']])->assertError(400, [
            'status' => '400',
            'detail' => 'The filter.name field must have a value.',
            'source' => ['parameter' => 'filter.name'],
        ]);
    }

    public function testReadRelatedWithSort()
    {
        $country = Country::factory()->create();

        $a = User::factory()->create([
            'name' => 'John Doe',
            'country_id' => $country->getKey(),
        ]);

        $b = User::factory()->create([
            'name' => 'Jane Doe',
            'country_id' => $country->getKey(),
        ]);

        $this->doReadRelated($country, 'users', ['sort' => 'name'])
            ->assertReadHasMany('users', [$b, $a]);
    }

    public function testReadRelatedWithInvalidSort()
    {
        $country = Country::factory()->create();

        // code is a valid sort on the countries resource, but not on the users resource.
        $this->doReadRelated($country, 'users', ['sort' => 'code'])->assertError(400, [
            'source' => ['parameter' => 'sort'],
            'detail' => 'Sort parameter code is not allowed.',
        ]);
    }

    public function testReadRelatedWithInclude()
    {
        $country = Country::factory()->create();
        $users = User::factory()->times(2)->create();
        $country->users()->saveMany($users);
        $phone = Phone::factory()->create(['user_id' => $users[0]->getKey()]);

        $this->doReadRelated($country, 'users', ['include' => 'phone'])
            ->assertReadHasMany('users', $users)
            ->assertIsIncluded('phones', $phone);
    }

    public function testReadRelatedWithInvalidInclude()
    {
        $country = Country::factory()->create();

        $this->doReadRelated($country, 'users', ['include' => 'foo'])->assertError(400, [
            'source' => ['parameter' => 'include'],
        ]);
    }

    public function testReadRelatedWithPagination()
    {
        $country = Country::factory()->create();
        $users = User::factory()->times(3)->create();
        $country->users()->saveMany($users);

        $this->doReadRelated($country, 'users', ['page' => ['number' => 1, 'size' => 2]])
            ->willSeeType('users')
            ->assertFetchedPage($users->take(2), null, ['current-page' => 1, 'per-page' => 2]);
    }

    public function testReadRelatedWithInvalidPagination()
    {
        $country = Country::factory()->create();

        $this->doReadRelated($country, 'users', ['page' => ['number' => 0, 'size' => 10]])->assertError(400, [
            'source' => ['parameter' => 'page.number'],
        ]);
    }

    public function testReadRelationship()
    {
        $country = Country::factory()->create();
        $users = User::factory()->times(2)->create();
        $country->users()->saveMany($users);

        $this->doReadRelationship($country, 'users')
            ->assertReadHasManyIdentifiers('users', $users);
    }

    public function testReadEmptyRelationship()
    {
        $country = Country::factory()->create();

        $this->doReadRelationship($country, 'users')
            ->assertReadHasManyIdentifiers(null);
    }

    public function testReplaceEmptyRelationshipWithRelatedResource()
    {
        $country = Country::factory()->create();
        $users = User::factory()->times(2)->create();

        $data = $users->map(function (User $user) {
            return ['type' => 'users', 'id' => (string) $user->getRouteKey()];
        })->all();

        $this->doReplaceRelationship($country, 'users', $data)
            ->assertStatus(204);

        $this->assertUsersAre($country, $users);
    }

    public function testReplaceRelationshipWithNone()
    {
        $country = Country::factory()->create();
        $users = User::factory()->times(2)->create();
        $country->users()->saveMany($users);

        $this->doReplaceRelationship($country, 'users', [])
            ->assertStatus(204);

        $this->assertFalse($country->users()->exists());
    }

    public function testReplaceRelationshipWithDifferentResources()
    {
        $country = Country::factory()->create();
        $country->users()->saveMany(User::factory()->times(2)->create());

        $users = User::factory()->times(3)->create();

        $data = $users->map(function (User $user) {
            return ['type' => 'users', 'id' => (string) $user->getRouteKey()];
        })->all();

        $this->doReplaceRelationship($country, 'users', $data)
            ->assertStatus(204);

        $this->assertUsersAre($country, $users);
    }

    public function testAddToRelationship()
    {
        $country = Country::factory()->create();
        $existing = User::factory()->times(2)->create();
        $country->users()->saveMany($existing);

        $add = User::factory()->times(2)->create();
        $data = $add->map(function (User $user) {
            return ['type' => 'users', 'id' => (string) $user->getRouteKey()];
        })->all();

        $this->doAddToRelationship($country, 'users', $data)
            ->assertStatus(204);

        $this->assertUsersAre($country, $existing->merge($add));
    }

    public function testRemoveFromRelationship()
    {
        $country = Country::factory()->create();
        $users = User::factory()->times(4)->create([
            'country_id' => $country->getKey(),
        ]);

        $data = $users->take(2)->map(function (User $user) {
            return ['type' => 'users', 'id' => (string) $user->getRouteKey()];
        })->all();

        $this->doRemoveFromRelationship($country, 'users', $data)
            ->assertStatus(204);

        $this->assertUsersAre($country, [$users->get(2), $users->get(3)]);
    }

    /**
     * @param $country
     * @param $user
     * @return void
     */
    private function assertUserIs(Country $country, User $user)
    {
        $this->assertUsersAre($country, [$user]);
    }

    /**
     * @param  Country  $country
     * @param  iterable  $users
     * @return void
     */
    private function assertUsersAre(Country $country, $users)
    {
        $this->assertSame(count($users), $country->users()->count());

        /** @var User $user */
        foreach ($users as $user) {
            $this->assertDatabaseHas('users', [
                'id' => $user->getKey(),
                'country_id' => $country->getKey(),
            ]);
        }
    }
}
