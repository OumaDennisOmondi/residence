<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResidentialAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('residential_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->string('email');
            $table->string('road');
            $table->integer('county_id')->unsigned();
            $table->integer('subcounty_id')->unsigned();
            $table->string('landmark');
            $table->string('building_name');
            $table->string('floor_no');
            $table->string('door_no');
            $table->string('image_path')->unique();
            $table->string('pin_location');
            $table->string('location_id');
            $table->string('address_id')->unique();
            $table->boolean('claimed')->default(0);
            $table->integer('owner_id')->unsigned()->nullable();
            $table->integer('created_by')->unsigned();
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
        Schema::dropIfExists('residential_addresses');
    }
}
