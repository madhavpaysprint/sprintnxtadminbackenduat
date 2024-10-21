<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankDetailsData extends Model
{
    use SoftDeletes;

    protected $connection = 'pgsql';
    protected $dates = ['deleted_at'];
    protected $fillable = ['bank_details_id', 'field_id', 'name', 'value', 'status'];
}
