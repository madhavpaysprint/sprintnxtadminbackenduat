<?php

namespace App\Models\Sample;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sample\Module;
use App\Models\Sample\ParentModule;
use App\Models\Role;  

class Permission extends Model
{
    protected $table = "sample_permissions";

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function parent()
    {
        return $this->belongsTo(ParentModule::class, 'parent_id');
    }


    protected $fillable = ["role_id", "parent_id", "module_id", "status"];
}
