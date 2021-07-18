<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDatabasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('databases', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->enum('database_driver', ['mysql', 'sqlsrv']);
            $table->string('database_name');
            $table->string('database_host');
            $table->unsignedSmallInteger('database_port');
            $table->string('database_username');
            $table->string('database_password')->nullable();
            $table->longText('extra_query')->nullable();
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
        Schema::dropIfExists('databases');
    }
}
