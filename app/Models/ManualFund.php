<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualFund extends Model
{
    protected $fillable = ["*"];
    protected $table = "manual_funds";


    public function doneByUser() {
        return $this->belongsTo(User::class, 'done_by'); // Assuming done_by is the foreign key
    }
    
    public function merchantUser() {
        return $this->belongsTo(MerchantUser::class, 'user_id'); // Assuming user_id is the foreign key
    }
    

}
