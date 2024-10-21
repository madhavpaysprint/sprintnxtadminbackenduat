<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Lumen\Auth\Authorizable;

class ApiUser extends Model
{
    use Authenticatable, Authorizable, HasFactory, SoftDeletes;

    protected $connection = 'pgsql';
    protected $table = 'users';
    protected $dates = ['deleted_at'];

    protected $guard_name = 'api';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'fullname',
        'email',
        'phone',
        'password',
        'status',
        'username',
        'lat',
        'lng',
        'role',

    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function apiConfig()
    {
        return $this->hasOne(ApiConfig::class, 'userid','id');
    }

    public function apiCredential()
    {
        return $this->hasOne(ApiCredential::class, 'userid', 'id');
    }
}
