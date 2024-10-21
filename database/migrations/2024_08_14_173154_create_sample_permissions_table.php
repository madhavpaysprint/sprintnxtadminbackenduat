<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSamplePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sample_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');  
            $table->unsignedBigInteger('module_id'); 
            $table->unsignedBigInteger('parent_id'); 
            $table->boolean('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sample_permissions');
    }
}
