<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogRequestResponseBank extends Model
{
    protected $fillable = ['user_id', 'service', 'api', 'uniquerefid','req_body', 'resp_body', 'req_date', 'req_time', 'resp_date','resp_time'];
    protected $connection = 'pgsql';
   

    public function userlog()
    {
        return $this->setConnection('mysql')->hasOne(User::class,'id','user_id');
    }
   
}
  

