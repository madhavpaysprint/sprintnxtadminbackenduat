<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiConfig extends Model
{
    protected $table = "api_config";
    protected $connection = 'pgsql';

    public function user()
    {
        return $this->belongsTo(User::class, 'userid');
    }
}
