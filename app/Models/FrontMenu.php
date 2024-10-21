<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrontMenu extends Model
{

    protected $table= 'front_menus';
    protected $connection = 'pgsql';
    protected $fillable = ['name','type','config_id','account_type','guard_name','urlapi','parent','icon','menu_order'];


    public function menuName(){
        return $this->hasOne('App\Models\FrontMenuPermission','menu_id');
    }
}