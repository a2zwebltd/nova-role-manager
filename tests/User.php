<?php

namespace A2ZWeb\NovaRoleManager\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    protected $guarded = [];

    protected $hidden = ['password'];
}
