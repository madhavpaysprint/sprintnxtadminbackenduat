<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserLoginLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_login_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('userid')->index();
            $table->string('ipaddress',20);
            $table->string('latlng',55);
            $table->string('device_name');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['id','userid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_login_logs');
    }
}
