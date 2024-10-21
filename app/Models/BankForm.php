<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BankFormDropdowns;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankForm extends Model
{
    protected $table= 'bank_forms';

    protected $connection = 'pgsql';
    protected $fillable = ['bank_id','fieldname','label','type','required','placeholder','value','index','status'];

    
    public function options(): HasMany
    {
        return $this->hasMany(BankFormDropdowns::class,'field_id','id');
    }

    public function values()
    {
        return $this->hasOne(BankDetailsData::class,'field_id','id');
    }

    public function remarks() : HasMany
    {
        return $this->hasMany(BankDetailsRemark::class,'field_id','id');
    }
}
