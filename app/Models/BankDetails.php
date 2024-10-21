<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankDetails extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    protected $dates = ['deleted_at'];
    protected $fillable = ['bank_id', 'user_id', 'status'];

    public function bank_id()
    {
        return $this->hasOne(BankList::class,'id','bank_id');
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    public function bankData() : HasMany
    {
        return $this->hasMany(BankDetailsData::class,'bank_details_id','id');
    }

    public function remarks() : HasMany
    {
        return $this->hasMany(BankDetailsRemark::class,'bank_details_id','id');
    }

     
}
