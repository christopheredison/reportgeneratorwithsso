<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnReportExcels2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('report_excels', function (Blueprint $table) {
            $table->string('file_source', 8)->change();
            $table->string('file_path', 100)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('report_excels', function (Blueprint $table) {
            $table->string('file_source', 7)->change();
            $table->string('file_path', 255)->change();
        });
    }
}
