<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPasswordReset extends Model
{
    protected $table= 'user_password_resets';
    protected $fillable = ['user_id','token','status'];

    public function user(){
        return $this->belongsTo('App\Models\User','user_id','id');
    }
}
