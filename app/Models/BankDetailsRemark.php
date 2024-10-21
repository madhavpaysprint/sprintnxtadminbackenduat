<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankDetailsRemark extends Model
{
   
    protected $connection = 'pgsql';
    protected $fillable = ['bank_details_id', 'document_id','created_at','updated_at'];
}
