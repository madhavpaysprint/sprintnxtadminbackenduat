<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Userloginlog extends Model
{
    use SoftDeletes;

    
    protected $dates = ['deleted_at'];
    protected $table = 'user_login_logs';
    protected $fillable = ['userid','ipaddress','latlng','device_name'];
    protected $timeStamps = 'Y-m-d H:i:s';
}
