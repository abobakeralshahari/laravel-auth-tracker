<?php

namespace Awsan\AuthTracker\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;

class UntrackedUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];
}
