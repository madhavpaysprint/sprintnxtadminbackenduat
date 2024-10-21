<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, HasFactory, SoftDeletes;

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

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


    public function getrole()
    {
        return $this->hasOne(Role::class, 'id', 'role');
    }

    public function bankaccounts(): HasMany
    {
        return $this->hasMany(BankDetails::class, 'user_id', 'id')->with('bank_id:id,name')->with('bankData:bank_details_id,name,value');
    }


    public function bank()
    {
        return $this->hasOne(BankList::class,'id','bank_id');
    }

    public function merchantUpis()
    {
        return $this->hasMany(MerchantUpi::class, 'userid', 'id');
    }

}
