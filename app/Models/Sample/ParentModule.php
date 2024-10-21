<?php

namespace App\Models\Sample;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sample\Module;


class ParentModule extends Model
{

    protected $table = "sample_parent_modules";

    public function modules()
    {
        return $this->hasMany(Module::class, 'parent_id');
    }
}