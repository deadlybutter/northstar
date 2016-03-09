<?php

use Northstar\Models\User;

class UserTest extends TestCase
{
    /**
     * Test for retrieving a user with a non-admin key.
     * GET /users/:term/:id
     *
     * @return void
     */
    public function testGetPublicDataFromUser()
    {
        // Test that we can view public profile of a seeded user.
        $this->withAuthorizedScopes(['user'])->get('v1/users/email/test@dosomething.org');
        $this->assertResponseStatus(200);
        $this->seeJsonStructure([
            'data' => [
                'id', 'email'
            ],
        ]);

        // And test that private profile fields are hidden for 'user' scope.
        $this->assertArrayNotHasKey('last_name', $this->decodeResponseJson()['data']);
    }

    /**
     * Test for retrieving a user with an admin key.
     * GET /users/:term/:id
     *
     * @return void
     */
    public function testGetAllDataFromUser()
    {
        $this->withAuthorizedScopes(['user', 'admin'])->get('v1/users/email/test@dosomething.org');
        $this->assertResponseStatus(200);

        // Check that public & private profile fields are visible
        $this->seeJsonStructure([
            'data' => [
                'id', 'email', 'last_name'
            ]
        ]);
    }

    /**
     * Test retrieving multiple users.
     * GET /users
     *
     * @return void
     */
    public function testIndex()
    {
        $this->get('v1/users');
        $this->assertResponseStatus(200);

        $this->seeJsonStructure([
            'data' => [
                '*' => [
                    'id',
                ],
            ],
            'meta' => [
                'pagination' => [
                   'total', 'count', 'per_page', 'current_page', 'links',
                ],
            ],
        ]);
    }

    /**
     * Test for retrieving a nonexistent User
     * GET /users/_id/FAKE
     *
     * @return void
     */
    public function testNonexistentUser()
    {
        $this->get('v1/users/_id/FAKE');
        $this->assertResponseStatus(404);
    }

    /**
     * Tests retrieving multiple users by their id
     * GET /users?filter[_id]=:id_1,...,:id_N
     * GET /users?filter[drupal_id]=:id_1,...,:id_N
     */
    public function testFilterUsersById()
    {
        // Retrieve multiple users by _id
        $this->get('v1/users?filter[_id]=5430e850dt8hbc541c37tt3d,5480c950bffebc651c8b456f,FAKE_ID');
        $this->assertCount(2, $this->decodeResponseJson()['data']);
        $this->seeJsonStructure([
            'data' => [
                '*' => [
                    'id',
                ],
            ],
            'meta' => [
                'pagination',
            ],
        ]);

        // Retrieve multiple users by drupal_id
        $this->get('v1/users?filter[drupal_id]=FAKE_ID,100001,100002,100003');
        $this->assertCount(3, $this->decodeResponseJson()['data']);

        // Test compound queries
        $this->get('v1/users?filter[drupal_id]=FAKE_ID,100001,100002,100003&filter[mobile]=5555550100');
        $this->assertCount(1, $this->decodeResponseJson()['data']);
    }

    /**
     * Tests searching users.
     * GET /users/?search[field]=term
     */
    public function testSearchUsers()
    {
        // Search should be limited to `admin` scoped keys.
        $this->get('v1/users?search[email]=test@dosomething.org');
        $this->assertResponseStatus(403);

        // Query by a "known" search term
        $this->withAuthorizedScopes(['admin'])
            ->get('v1/users?search[_id]=test@dosomething.org&search[email]=test@dosomething.org');
        $this->assertResponseStatus(200);

        // There should be one match (a user with the provided email)
        $this->assertCount(1, $this->decodeResponseJson()['data']);
    }

    /**
     * Tests retrieving a user
     * GET /users/{term}/{id}
     */
    public function testRetrieveUser()
    {
        // User info
        $user = User::create([
            'email' => 'sterling.archer@example.com',
            'mobile' => '5551231245',
            'drupal_id' => '4567890',
        ]);

        // GET /users/_id/<user_id>
        $this->get('v1/users/_id/'.$user->_id);

        // Assert response is 200 and has expected data
        $this->assertResponseStatus(200);
        $this->seeJsonSubset([
            'data' => [
                'id' => $user->_id,
                'email' => $user->email,
            ],
        ]);

        // GET /users/mobile/<mobile>
        $this->get('v1/users/mobile/'.$user->mobile);
        $this->assertResponseStatus(200);
        $this->seeJsonSubset([
            'data' => [
                'id' => $user->_id,
                'mobile' => $user->mobile,
            ],
        ]);

        // GET /users/email/<email>
        $this->get('v1/users/email/'.$user->email);
        $this->assertResponseStatus(200);
        $this->seeJsonSubset([
            'data' => [
                'id' => $user->_id,
                'email' => $user->email,
            ],
        ]);

        // GET /users/drupal_id/<drupal_id>
        $this->get('v1/users/drupal_id/'.$user->drupal_id);
        $this->assertResponseStatus(200);
        $this->seeJsonSubset([
            'data' => [
                'id' => $user->_id,
                'drupal_id' => $user->drupal_id,
            ],
        ]);
    }

    /**
     * Test for creating a new user.
     * POST /users
     *
     * @return void
     */
    public function testCreateUser()
    {
        // Create a new user object
        $payload = [
            'email' => 'new@dosomething.org',
            'source' => 'phpunit',
        ];

        $this->withAuthorizedScopes(['admin'])->json('POST', 'v1/users', $payload);
        $this->assertResponseStatus(200);
        $this->seeJsonStructure([
            'data' => [
                'id', 'email', 'source', 'created_at',
            ],
        ]);
    }

    /**
     * Test for "upserting" an existing user.
     * POST /users
     *
     * @return void
     */
    public function testUpsertUser()
    {
        User::create([
            'email' => 'upsert-me@dosomething.org',
            'source' => 'database',
        ]);

        // Post a "new" user object to merge into existing record
        $this->withAuthorizedScopes(['admin'])->json('POST', 'v1/users', [
            'email' => 'upsert-me@dosomething.org',
            'mobile' => '5556667777',
            'password' => 'secret',
            'first_name' => 'Puppet',
            'source' => 'phpunit',
        ]);

        // The response should return JSON with a 200 Okay status code
        $this->assertResponseStatus(200);
        $this->seeJsonSubset([
            'data' => [
                'email' => 'upsert-me@dosomething.org',
                // Check for the new fields we "upserted":
                'first_name' => 'Puppet',
                'mobile' => '5556667777',
                // Ensure the `source` field is immutable (since we tried to update to 'phpunit'):
                'source' => 'database',
            ]
        ]);
    }

    /**
     * Test for updating an existing user
     * PUT /users/_id/:id
     *
     * @return void
     */
    public function testUpdateUser()
    {
        // Create a new user object
        $this->withAuthorizedScopes(['admin'])->json('PUT', 'v1/users/_id/5480c950bffebc651c8b456f', [
            'email' => 'newemail@dosomething.org',
            'parse_installation_ids' => 'parse-abc123',
        ]);

        $this->assertResponseStatus(200);
        $this->seeJsonSubset([
            'data' => [
                'email' => 'newemail@dosomething.org',
                'parse_installation_ids' => ['parse-abc123'],
                'mobile' => '5555550101' // unchanged user values should remain unchanged
            ],
        ]);

        // Verify user data got updated
        $this->seeInDatabase('users', [
            '_id' => '5480c950bffebc651c8b456f',
            'email' => 'newemail@dosomething.org',
            'parse_installation_ids' => ['parse-abc123'],
            'mobile' => '5555550101'
        ]);
    }

    /**
     * Test for creating a user's profile image with a file
     * POST /users/:user_id/avatar
     *
     * @return void
     */
    public function testCreateUserAvatarWithFile()
    {
        $user = User::find('5480c950bffebc651c8b456f');

        // Mock successful response from AWS API
        $this->mock('Northstar\Services\AWS')->shouldReceive('storeImage')->once()->andReturn('http://bucket.s3.amazonaws.com/5480c950bffebc651c8b456f-1234567.jpg');

        $this->asUser($user)->withAuthorizedScopes(['user'])->json('POST', 'v1/users/5480c950bffebc651c8b456f/avatar', [
            'photo' => 'example.jpeg',
        ]);

        $this->assertResponseStatus(200);
        $this->seeJsonSubset([
            'data' => [
                'photo' => 'http://bucket.s3.amazonaws.com/5480c950bffebc651c8b456f-1234567.jpg'
            ],
        ]);
    }

    /**
     * Test for creating a user's profile image with a Base64 string
     * POST /users/:user_id/avatar
     *
     * @return void
     */
    public function testCreateUserAvatarWithBase64()
    {
        $user = User::find('5480c950bffebc651c8b456f');

        // Mock successful response from AWS API
        $this->mock('Northstar\Services\AWS')->shouldReceive('storeImage')->once()->andReturn('http://bucket.s3.amazonaws.com/5480c950bffebc651c8b456f-123415.jpg');

        $this->asUser($user)->withAuthorizedScopes(['user'])->json('POST', 'v1/users/5480c950bffebc651c8b456f/avatar', [
            'photo' => '123456789',
        ]);

        $this->assertResponseStatus(200);
        $this->seeJsonSubset([
            'data' => [
                'photo' => 'http://bucket.s3.amazonaws.com/5480c950bffebc651c8b456f-123415.jpg'
            ],
        ]);
    }

    /**
     * Test for deleting an existing user
     * DELETE /users
     *
     * @return void
     */
    public function testDelete()
    {
        // Only 'admin' scoped keys should be able to delete users.
        $this->delete('v1/users/5480c950bffebc651c8b4570');
        $this->assertResponseStatus(403);

        $this->withAuthorizedScopes(['admin'])->delete('v1/users/5480c950bffebc651c8b4570');
        $this->assertResponseStatus(200);
    }

    /**
     * Test for deleting a user that does not exist.
     * DELETE /users
     *
     * @return void
     */
    public function testDeleteNoResource()
    {
        $this->withAuthorizedScopes(['admin'])->delete('v1/users/DUMMY_ID');
        $this->assertResponseStatus(404);
    }
}
