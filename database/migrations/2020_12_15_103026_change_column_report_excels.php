<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Doctrine\DBAL\Types\StringType; 
use Doctrine\DBAL\Types\Type;

class ChangeColumnReportExcels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('report_excels', function (Blueprint $table) {
            $table->string('file_path', 255)->change();
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
            $table->string('file_path', 200)->change();
        });
    }
}
