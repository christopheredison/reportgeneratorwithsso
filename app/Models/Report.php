<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    public function reportQuery() {
    	return $this->hasOne(ReportQuery::class);
    }

    public function reportExcel() {
    	return $this->hasOne(ReportExcel::class);
    }
}
