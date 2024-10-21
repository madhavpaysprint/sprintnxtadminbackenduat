<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicForm extends Model
{
    protected $table = "dynamic_form_config";

    protected $connection = 'pgsql';

    protected $fillable = ["bank_id","common_json", "created_at", "updated_at"];
}
