<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BankList;
use App\Models\NewService;

class NewDefaultCharge extends Model
{
    protected $fillable = ['bank_id', 'service_id', 'charges', 'is_fixed', 'min', 'max']; 
    protected $connection = 'pgsql';

    public function bank()
    {
        return $this->belongsTo(BankList::class, 'bank_id');
    }

    public function service()
    {
        return $this->belongsTo(NewService::class, 'service_id');
    }
}
