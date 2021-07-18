<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Database extends Model
{
    const DATABASE_DRIVER_MYSQL = 'mysql';
    const DATABASE_DRIVER_SQLSRV = 'sqlsrv';
    const DATABASE_DRIVER = [Database::DATABASE_DRIVER_MYSQL, Database::DATABASE_DRIVER_SQLSRV];

    use HasFactory;
    protected $fillable = [
        'name',
        'database_driver',
        'database_name',
        'database_host',
        'database_port',
        'database_username',
        'database_password',
        'extra_query',
    ];

    public function getDatabasePasswordAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function databaseFilter()
    {
        return $this->hasMany(DatabaseFilter::class, 'database_id');
    }
}
