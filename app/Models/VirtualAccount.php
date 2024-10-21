<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualAccount extends Model{
    protected $fillable = ["*"];

    protected $connection = 'pgsql';
    protected $table = "va";
    protected $dates = [
        'created_at',
        'updated_at'
        ];

    public function whitelistAccounts()
    {
        return $this->hasMany(VirtualAccountWhitelisting::class, 'va_id','id');
    }
}