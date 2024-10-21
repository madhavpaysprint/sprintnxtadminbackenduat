<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargesDefault extends Model
{
    protected $table= 'charges_default';
    protected $fillable = ["*"]; 
    protected $connection = 'pgsql';
    
}
