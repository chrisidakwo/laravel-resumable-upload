<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**s
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('resumable-upload.table_name'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('token',64)->unique();
            $table->string('handler');
            $table->string('name');
            $table->string('type');
            $table->string('extension');
            $table->integer('chunks');
            $table->bigInteger('size');
            $table->json('payload');
            $table->boolean('is_complete')->default(0);
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
        Schema::dropIfExists(config('resumable-upload.table_name'));
    }
};
