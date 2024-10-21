<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationStatus extends Model
{
    protected $table = "notification_status";
    protected $fillable = ['user_id', 'notification_id', 'status'];
    protected $connection = 'pgsql';
    public function notice(){
        return $this->belongsTo(Notification::class,'notification_id');
    }

    public function type(){
        return $this->belongsTo(User::class,'user_id');
    }
}
