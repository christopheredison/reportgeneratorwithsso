<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDatabaseFiltersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('database_filters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('database_id');
            $table->unsignedBigInteger('connection')->nullable();
            $table->string('identifier');
            $table->text('query');
            $table->timestamps();

            $table->foreign('database_id')->on('databases')->references('id')->onDelete('cascade');
            $table->foreign('connection')->on('databases')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('database_filters');
    }
}
