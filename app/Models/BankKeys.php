<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class BankKeys extends Model
{
    protected $table= 'bank_keys';
    protected $connection = 'pgsql';
    protected $fillable = ['id','user_id','bank_id','corp_id','approver_id', 'maker_id',
        'checker_id', 'signature', 'ldap_user_id', 'ldap_password', 'secret_id', 'client_id', 'ssl_certificate',
        'ssl_private_key', 'ssl_public_key', 'status'];

    public function banklist(){
        return $this->belongsTo(BankList::class, 'bank_id', 'id');
    }
}
