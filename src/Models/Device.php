<?php

namespace Alshahari\AuthTracker\Models;

use Illuminate\Database\Eloquent\Model;


class Device extends Model
{
    protected $guarded = [];

    public function __construct()
    {
//        $this->setTable(config('auth_tracker.table_name'));
        $this->setConnection(config('auth_tracker.connection'));

    }
    
    public function deviceable()
    {
        return $this->morphTo();
    }

    public function logins(): HasMany
    {
//        $model = config('auth-checker.models.login') ?? Login::class;
        $model = Login::class;
        return $this->hasMany($model,'device_id','id');

    }

    public function login(): HasOne
    {
//        $model = config('auth-checker.models.login') ?? Login::class;
        $model =  Login::class;
        $relation = $this->hasOne($model);
        $relation->orderBy('created_at', 'desc');
        return $relation;
    }

//    public function user(): MorphTo
//    {
//        return $this->morphTo();
//    }
    
}
