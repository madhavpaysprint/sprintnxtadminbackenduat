<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrontMenuPermission extends Model
{
    protected $connection = 'pgsql';
    public function role(){
        return $this->belongsTo(Role::class,'role_id');
    }

    public function menu(){
        return $this->belongsTo(FrontMenu::class,'menu_id');
    }
}
