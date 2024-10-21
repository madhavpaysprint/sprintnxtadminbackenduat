<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    // protected $table= 'notifications';
    protected $fillable = ['title', 'content','user_type', 'start_date', 'end_date', 'status'];
    protected $connection = 'pgsql';
    public function noticeName(){
        return $this->hasOne('App\Models\NotificationStatus','notification_id');
    }

    public function noticeType(){
        return $this->hasOne('App\Models\NotificationStatus','user_id');
    }
}
