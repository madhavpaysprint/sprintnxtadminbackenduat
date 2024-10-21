<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantUpi extends Model{
    protected $connection = 'pgsql';
    protected $fillable = ["*"];
    protected $table = 'merchant_upis';

    public function qr(){
        return $this->belongsTo(MerchantQr::class, 'merchantID','merchantID');
    }



    // public function qroption(): HasMany
    // {
    //     return $this->hasMany(MerchantQr::class,'refId', 'qr_refid',);
    // }

    public function vpa()
    {
        return $this->belongsTo(MerchantVpa::class, 'merchantID', 'merchant_code');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userid', 'id');
    }

    public function bank()
    {
        return $this->belongsTo(BankList::class, 'bank_id', 'id');
    }
}
