<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogRequestResponseApi extends Model
{
    protected $fillable = ['username', 'client_fullname', 'client_mobile', 'client_email', 'service', 'api', 'uniquerefid', 'req_body', 'resp_body', 'req_date', 'req_time', 'resp_date', 'resp_time'];
    protected $connection = 'pgsql';
   
    public function userlog()
    {
        return $this->setConnection('mysql')->hasOne(User::class,'id','user_id');
    }
   
}
  

