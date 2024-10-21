<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankFormDropdowns extends Model
{
    public $timestamps = true;
    protected $table= 'bank_form_dropdowns';
    protected $fillable = ['field_id','name','value'];

    protected $connection = 'pgsql';

    
}
