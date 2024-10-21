<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminMenu extends Model
{

    protected $table= 'admin_menus';
    protected $fillable = ['name','type','config_id','module_id','guard_name','urlapi','parent','icon','menu_order'];


    public function menuName(){
        return $this->hasOne('App\Models\AdminMenuPermission','menu_id');
    }
}