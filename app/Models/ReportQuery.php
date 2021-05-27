<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportQuery extends Model
{
	protected $table = 'report_queries';

    public function report() {
    	return $this->belongsTo(Report::class);
    }
}
