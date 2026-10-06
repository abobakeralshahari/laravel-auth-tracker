<?php

namespace OwaisKit\AuthTracker\Tests;

use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

class PassportTest extends TestCase
{
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = app(ClientRepository::class)->createPasswordGrantClient('Test', 'passport_users', true);
    }

    public function test_access_token_is_tracked(): void
    {
        $user = $this->createUser(PassportUser::class);

        $accessToken = $this->authenticate($user)->json('access_token');

        $this->assertCount(1, $user->logins);

        $response = $this->getJson('/api/check', ['Authorization' => 'Bearer '.$accessToken]);

        $response->assertOk();
        $this->assertNotEmpty($response->json('id'));
        $this->assertTrue($response->json('is_current'));
    }

    public function test_logout_others_revokes_the_other_tokens(): void
    {
        $user = $this->createUser(PassportUser::class);

        $first = $this->authenticate($user)->json('access_token');
        $second = $this->authenticate($user)->json('access_token');

        $this->postJson('/api/logout/others', [], ['Authorization' => 'Bearer '.$second])->assertOk();

        // The request guard caches the resolved user for the lifetime of the
        // application instance, so reset it between simulated requests.
        Auth::forgetGuards();
        $this->getJson('/api/check', ['Authorization' => 'Bearer '.$first])->assertUnauthorized();

        Auth::forgetGuards();
        $this->getJson('/api/check', ['Authorization' => 'Bearer '.$second])->assertOk();
        $this->assertCount(1, $user->activeLogin());
    }

    public function test_refreshing_a_token_rotates_the_login_instead_of_creating_one(): void
    {
        $user = $this->createUser(PassportUser::class);

        $tokens = $this->authenticate($user)->json();
        $login = $user->logins()->first();

        $refreshed = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $tokens['refresh_token'],
            'client_id' => $this->client->getKey(),
            'client_secret' => $this->client->plainSecret,
            'scope' => '',
        ])->assertOk()->json();

        $this->assertCount(1, $user->logins()->withExpired()->get());
        $this->assertSame(1, $login->fresh()->rotations);
        $this->assertNotSame($login->credential_id, $login->fresh()->credential_id);

        Auth::forgetGuards();
        $this->getJson('/api/check', ['Authorization' => 'Bearer '.$refreshed['access_token']])
            ->assertOk()
            ->assertJsonPath('id', $login->id)
            ->assertJsonPath('is_current', true);
    }

    protected function authenticate($user)
    {
        return $this->postJson('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $this->client->getKey(),
            'client_secret' => $this->client->plainSecret,
            'username' => $user->email,
            'password' => 'password',
            'scope' => '',
        ])->assertOk();
    }
}
