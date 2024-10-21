<?php

namespace App\Models\Sample;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sample\ParentModule;
use App\Models\Sample\Permission;


class Module extends Model
{

    protected $table = "sample_modules";

    protected $fillable = [
       "parent_id", "name", "status", "url", "icon"
    ];

    public function parentModule()
    {
        return $this->belongsTo(ParentModule::class);
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'module_id');
    }
}