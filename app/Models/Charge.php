<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Charge extends Model
{
    protected $table= 'charges';
    protected $fillable = ["*"]; 
    protected $connection = 'pgsql';
    
}
