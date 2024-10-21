<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewDefaultChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('pgsql')->create('new_default_charges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_id');
            $table->unsignedBigInteger('service_id');
            $table->string('charges');
            $table->string('min')->nullable();
            $table->string('max')->nullable();
            $table->string('is_fixed')->nullable();
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
        Schema::connection('pgsql')->dropIfExists('new_default_charges');
    }
}
