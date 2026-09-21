<?php

namespace Alshahari\AuthTracker\Tests;

use Alshahari\AuthTracker\Events\PersonalAccessTokenCreated;
use Laravel\Sanctum\PersonalAccessToken;

class SanctumTest extends TestCase
{
    public function test_personal_access_token_is_tracked(): void
    {
        $user = $this->createUser();

        $token = $user->createToken('phone');
        event(new PersonalAccessTokenCreated($token));

        $login = $user->logins()->where('personal_access_token_id', $token->accessToken->id)->first();

        $this->assertNotNull($login);

        $response = $this->getJson('/sanctum/check', ['Authorization' => 'Bearer '.$token->plainTextToken]);

        $response->assertOk();
        $this->assertSame($login->getKey(), $response->json('id'));
        $this->assertTrue($response->json('is_current'));
    }

    public function test_revoking_a_login_deletes_the_sanctum_token(): void
    {
        $user = $this->createUser();

        $first = $user->createToken('phone');
        event(new PersonalAccessTokenCreated($first));

        $second = $user->createToken('tablet');
        event(new PersonalAccessTokenCreated($second));

        $response = $this->postJson('/sanctum/logout/others', [], ['Authorization' => 'Bearer '.$second->plainTextToken]);

        $response->assertOk();
        $this->assertNull(PersonalAccessToken::find($first->accessToken->id));
        $this->assertNotNull(PersonalAccessToken::find($second->accessToken->id));
        $this->assertCount(1, $user->activeLogin());
    }
}
