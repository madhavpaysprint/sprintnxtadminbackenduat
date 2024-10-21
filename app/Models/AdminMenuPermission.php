<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminMenuPermission extends Model
{
    
    public function menu(){
        return $this->belongsTo(AdminMenu::class,'menu_id');
    }
}
