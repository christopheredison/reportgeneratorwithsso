<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportExcel extends Model
{
    public function report() {
    	return $this->belongsTo(Report::class);
    }
}
