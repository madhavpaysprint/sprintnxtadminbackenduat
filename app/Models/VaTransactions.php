<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VaTransactions extends Model{
    protected $fillable = ["*"];
    protected $connection = 'pgsql';
    protected $table = "va_transactions";

    public function va(){
        return $this->hasOne(VirtualAccount::class, 'acc_no','va_no');
       // return $this->hasMany(MerchantQr::class,'refId', 'qr_refid',)
    }
}