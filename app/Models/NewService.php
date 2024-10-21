<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewService extends Model
{
    

    protected $fillable = ["*"]; 
    protected $connection = 'pgsql';
}
