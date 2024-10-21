<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantUser extends Model
{
    protected $table= 'users';
    protected $fillable = ["*"]; 
    protected $connection = 'pgsql';
    
}
