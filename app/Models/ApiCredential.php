<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiCredential extends Model
{
    protected $table = "api_credentials";
    protected $connection = 'pgsql';

    public function user()
    {
        return $this->belongsTo(User::class, 'userid');
    }
}
