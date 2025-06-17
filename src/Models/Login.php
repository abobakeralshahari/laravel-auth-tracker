<?php

namespace Alshahari\AuthTracker\Models;

use Alshahari\AuthTracker\EloquentQueryBuilder;
use Alshahari\AuthTracker\Traits\ManagesLogins;
use Alshahari\AuthTracker\Traits\Expirable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
//use Lab404\AuthChecker\Models\Device;
use Alshahari\AuthTracker\Models\Device;

class Login extends Model
{
    use Expirable, ManagesLogins, SoftDeletes;

    const EXPIRES_AT = 'expires_at';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'expires_at'
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'authenticatable_type',
        'authenticatable_id',
        'session_id',
        'remember_token',
        'oauth_access_token_id',
        'personal_access_token_id',
        'expires_at',
        'deleted_at',

        'device_id',
'updated_at',
        'device_type',
        'ip_data',
        'platform',
        'browser',

        'cleared_by_user',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['is_current'];

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->setTable(config('auth_tracker.table_name'));
        $this->setConnection(config('auth_tracker.connection'));

        parent::__construct($attributes);
    }

    /**
     * Relation between Login and an authenticatable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function authenticatable()
    {
        return $this->morphTo();
    }

    /**
     * Add the "location" attribute to get the IP address geolocation.
     *
     * @return string|null
     */
    public function getLocationAttribute()
    {
        $location = [
            $this->city,
            $this->region,
            $this->country,
        ];

        return array_filter($location) ? implode(', ', $location) : null;
    }

    /**
     * Dynamicly add the "is_current" attribute.
     *
     * @return bool
     */
    public function getIsCurrentAttribute()
    {
        if ($this->session_id && request()->hasSession()) {
            // Session
            return $this->session_id === request()->session()->getId();

        } elseif ($this->oauth_access_token_id && request()->user()->isAuthenticatedByPassport()) {

            // Passport

            return $this->oauth_access_token_id === request()->user()->token()->id;

        } elseif ($this->personal_access_token_id && request()->user()->isAuthenticatedBySanctum()) {

            // Sanctum

            return $this->personal_access_token_id === request()->user()->currentAccessToken()->id;
        }

        return false;
    }

    /**
     * Revoke the login.
     *
     * @return mixed
     * @throws \Exception
     */
    public function revoke()
    {

        if ($this->session_id) {

            // Destroy session
            $this->destroySession($this->session_id);

        } elseif ($this->oauth_access_token_id) {

            // Revoke Passport token
            $this->revokePassportTokens($this->oauth_access_token_id);

        } elseif ($this->personal_access_token_id) {

            // Revoke Sanctum token
            $this->revokeSanctumTokens($this->personal_access_token_id);

        }

        // Delete login
       // return $this->delete();
    //   return $this->revokeDelete();
            $date=Carbon::now()->toDateTimeString();
           $this->update(['cleared_by_user'=>true,'logout_at'=>$date]);
    }
    
    public function revokeDelete(){
        $date=Carbon::now()->toDateTimeString();
        return    $this->update(['cleared_by_user'=>true,'logout_at'=>$date]);
    }


    public function logout()
    {

        if ($this->session_id) {

            // Destroy session
            $this->destroySession($this->session_id);

        } elseif ($this->oauth_access_token_id) {

            // Revoke Passport token
            $this->revokePassportTokens($this->oauth_access_token_id);

        } elseif ($this->personal_access_token_id) {

            // Revoke Sanctum token
            $this->revokeSanctumTokens($this->personal_access_token_id);

        }

        // Delete login
        // return $this->delete();
        //   return $this->revokeDelete();
        $date=Carbon::now()->toDateTimeString();
        $this->update(['cleared_by_user'=>true,'logout_at'=>$date]);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new EloquentQueryBuilder($query);
    }


//    public function user(): MorphTo
//    {
//        return $this->morphTo();
//    }

    public function device()
    {
//        $model = config('auth-checker.models.device') ?? Device::class;
        $model =  Device::class;

        return $this->belongsTo($model,'device_id','id');
    }
}
