<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatabaseFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'database_id',
        'identifier',
        'connection',
        'query'
    ];
}
