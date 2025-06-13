<?php

namespace Alshahari\AuthTracker\Traits;

use Alshahari\AuthTracker\Models\Login;
use Illuminate\Database\Eloquent\Builder;

trait AuthTracking
{
    /**
     * Get all of the user's logins.
     */
    public function logins()
    {
        return $this->morphMany('Alshahari\AuthTracker\Models\Login', 'authenticatable');
    }

    /**
     * Get the current user's login.
     *
     * @return Login|null
     */
    public function currentLogin()
    {
        if ($this->isAuthenticatedBySession()) {

            return $this->logins()
                ->where('session_id', session()->getId())
                ->first();

        } elseif ($this->isAuthenticatedByPassport()) {

       $token=auth()->user()->token()->id;
            return $this->logins()
               // ->where('oauth_access_token_id', $this->token()->id)
                ->where('oauth_access_token_id', $token)
                ->first();

        } elseif ($this->isAuthenticatedBySanctum()) {

            return $this->logins()
                ->where('personal_access_token_id', $this->currentAccessToken()->id)
                ->first();

        }

        return null;
    }

    /**
     * Destroy a session / Revoke an access token by its ID.
     *
     * @param int|null $loginId
     * @return bool
     * @throws \Exception
     */
    public function logout($loginId = null)
    {
        $login = $loginId ? $this->logins()->find($loginId) : $this->currentLogin();

        return $login ? (!empty($login->revoke())) : false;
    }

    /**
     * Destroy all sessions / Revoke all access tokens, except the current one.
     *
     * @return mixed
     */
    public function logoutOthers()
    {

        if ($this->isAuthenticatedBySession()) {
            $login = $this->logins()
                ->where(function (Builder $query) {
                    return $query
                        ->where('session_id', '!=', session()->getId())
                        ->orWhereNull('session_id');
                })->get();
//                        ->revoke();

        } elseif ($this->isAuthenticatedByPassport()) {

//            return $this->logins()
            $login = $this->logins()
                ->where(function (Builder $query) {
                    return $query
//                                ->where('oauth_access_token_id', '!=', $this->token()->id)
                        ->where('oauth_access_token_id', '!=', auth()->user()->token()->id)
                        ->orWhereNull('oauth_access_token_id');
                })->get();
            //   ->revoke();

        } elseif ($this->isAuthenticatedBySanctum()) {

//            return $this->logins()
            $login = $this->logins()
                ->where(function (Builder $query) {
                    return $query
                        ->where('personal_access_token_id', '!=', $this->currentAccessToken()->id)
                        ->orWhereNull('personal_access_token_id');
                })->get();
            //   ->revoke();
        }

        if ($login->count() > 0) {
            foreach ($login as $item) {
                $item->revoke();
            }

            return true;
        }

        return false;
    }

    /**
     * Destroy all sessions / Revoke all access tokens.
     *
     * @return mixed
     */
    public function logoutAll()
    {
        $login = $this->logins()
            ->where('logout_at', null)
            ->where('cleared_by_user', false)
            ->get();

        if ($login->count() > 0) {
            foreach ($login as $item) {
                $item->revoke();
            }
            return true;
        }
        return false;
//        return $this->logins()->revoke();
    }

    /**
     * Determine if current user is authenticated via a session.
     *
     * @return bool
     */
    public function isAuthenticatedBySession()
    {
        return request()->hasSession();
    }

    /**
     * Check for authentication via Passport.
     *
     * @return bool
     */
    public function isAuthenticatedByPassport()
    {

        return in_array('Laravel\Passport\HasApiTokens', class_uses($this))
            && !is_null(auth()->user()->token());
//            && ! is_null($this->token());
    }

    /**
     * Check for authentication via Sanctum.
     *
     * @return bool
     */
    public function isAuthenticatedBySanctum()
    {
        return in_array('Laravel\Sanctum\HasApiTokens', class_uses($this))
            && !is_null($this->currentAccessToken());
    }

    public function activeLogin()
    {

        return $this->logins()
            ->where('logout_at', null)
            ->where('cleared_by_user', false)
            ->with('device:id,udid as ud_id,os,os_version,manufacturer,model,app_version')
            //   ->select(['id','created_at','ip','city','region','country','device_type','device_id','login_by','login_from','user_agent'])
            ->orderBy('updated_at', 'desc')
            ->get();
    }


    public function historyLogin()
    {
        return $this->logins()
            ->where('logout_at', '!=', null)
            ->where('cleared_by_user', true)
            ->with('device:id,os,os_version,manufacturer,model,app_version')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function clearHistoryId($id)
    {
        return $this->logins()
            ->where('id', $id)
            ->where('logout_at', '!=', null)
            ->where('cleared_by_user', true)
            ->delete();
    }

    public function clearHistory()
    {
        return $this->logins()
            ->where('logout_at', '!=', null)
            ->where('cleared_by_user', true)
            ->delete();
    }
}
