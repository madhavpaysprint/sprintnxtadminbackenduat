<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fullname', 55)->nullable();
            $table->string('username', 15);
            $table->string('email', 50)->unique();
            $table->string('password')->nullable();
            $table->bigInteger('phone')->unique();
            $table->Integer('branch_id')->nullable();
            $table->tinyInteger('role')->default(0)->nullable()->comment('eg.admin,user..');
            $table->tinyInteger('firstlogin')->nullable();
            $table->string('allowip', 50)->nullable();
            $table->string('ipaddress', 20)->nullable();
            $table->string('lat', 55)->nullable();
            $table->string('lng', 55)->nullable();
            $table->tinyInteger('status')->default(2)->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['id', 'role', 'email', 'phone', 'username']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
