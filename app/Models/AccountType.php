<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    protected $connection = 'pgsql';
    protected $fillable = ['type', 'status'];
}
