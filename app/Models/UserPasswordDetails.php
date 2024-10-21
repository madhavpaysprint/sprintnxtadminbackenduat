<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class UserPasswordDetails extends Model
{ 

    protected $table= 'user_password';
    protected $fillable = ['user_id','password','expired_at', 'status'];


    public function user(){
        return $this->belongsTo('App\Models\User','user_id','id');
    }
}
