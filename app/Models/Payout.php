<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Payout extends Model
{
    protected $connection = 'pgsql';
    public function users(){
        return $this->belongsTo(User::class, 'userid','id');
    }
}
