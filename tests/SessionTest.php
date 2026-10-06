<?php

namespace Awsan\AuthTracker\Tests;

use Awsan\AuthTracker\Models\Login;
use Illuminate\Support\Facades\Auth;

class SessionTest extends TestCase
{
    public function test_login_is_tracked_and_bound_to_the_session(): void
    {
        $user = $this->createUser();

        Auth::login($user);

        $login = $user->logins()->where('session_id', session()->getId())->first();

        $this->assertNotNull($login);
        $this->assertSame($login->getKey(), session()->get(Login::SESSION_KEY));
        $this->assertNotNull($login->device_id);
        $this->assertTrue($user->currentLogin()->is($login));
    }

    public function test_current_login_survives_a_session_regeneration(): void
    {
        $user = $this->createUser();

        Auth::login($user);
        $login = $user->currentLogin();

        session()->regenerate();

        $this->assertTrue($user->currentLogin()->is($login));
        $this->assertTrue($user->currentLogin()->is_current);
    }

    public function test_logout_marks_the_login_as_revoked(): void
    {
        $user = $this->createUser();

        Auth::login($user);
        $login = $user->currentLogin();

        Auth::logout();

        $this->assertTrue($login->fresh()->cleared_by_user);
        $this->assertNotNull($login->fresh()->logout_at);
        $this->assertFalse(session()->has(Login::SESSION_KEY));
    }

    public function test_logout_others_keeps_the_current_login(): void
    {
        $user = $this->createUser();

        $other = $user->logins()->save(new Login(['session_id' => 'other-session']));

        Auth::login($user);
        $current = $user->currentLogin();

        $this->assertTrue($user->logoutOthers());

        $this->assertTrue($other->fresh()->cleared_by_user);
        $this->assertFalse($current->fresh()->cleared_by_user);
        $this->assertCount(1, $user->activeLogin());
    }

    public function test_logout_all_revokes_everything(): void
    {
        $user = $this->createUser();
        $user->logins()->save(new Login(['session_id' => 'other-session']));

        Auth::login($user);

        $this->assertTrue($user->logoutAll());
        $this->assertCount(0, $user->activeLogin());
        $this->assertCount(2, $user->historyLogin());
    }

    public function test_remember_token_is_stored_per_login(): void
    {
        $user = $this->createUser();

        Auth::login($user, true);

        $login = $user->currentLogin();

        $this->assertNotNull($login->remember_token);
        $this->assertNotSame($login->remember_token, $user->fresh()->getRememberToken());

        $provider = Auth::createUserProvider('users');

        $this->assertTrue($provider->retrieveByToken($user->getKey(), $login->remember_token)->is($user));
    }

    public function test_revoked_login_cannot_be_recalled_by_its_remember_token(): void
    {
        $user = $this->createUser();

        Auth::login($user, true);

        $login = $user->currentLogin();
        $token = $login->remember_token;

        $login->revoke();

        $this->assertNull(Auth::createUserProvider('users')->retrieveByToken($user->getKey(), $token));
    }

    public function test_untracked_users_are_ignored(): void
    {
        $user = UntrackedUser::create([
            'name' => 'Nobody', 'email' => 'nobody@example.com', 'password' => bcrypt('x'),
        ]);

        Auth::login($user);

        $this->assertSame(0, Login::count());
    }
}
