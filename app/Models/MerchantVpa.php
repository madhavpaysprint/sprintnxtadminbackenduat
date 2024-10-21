<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchantVpa extends Model
{
    protected $connection = 'pgsql';
    protected $dates = ['deleted_at'];
    protected $fillable = ["*"];
    protected $table = 'merchant_vpas_copy';

    // public function qrAmount(){
    //     return $this->belongsTo(MerchantUpi::class, 'merchantID','merchantID');
    // }

    public function merchantUpis()
    {
        return $this->belongsTo(MerchantUpi::class, 'merchantID', 'merchant_code');
    }
}
