<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModulePermission extends Model
{
    protected $dates = ['deleted_at'];
    protected $fillable = ['module_id', 'permission'];
}
