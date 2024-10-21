<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrontRole extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'roles';
    protected $dates = ['deleted_at'];
    protected $fillable = ['role', 'status'];

}
