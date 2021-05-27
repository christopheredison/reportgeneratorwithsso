<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\OnedriveController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
Route::get('/reports/{id}/export', [ReportController::class, 'export']);
Route::get('/reports/{id}/edit', [ReportController::class, 'edit'])->name('reports.edit');
Route::get('/reports/preview', [ReportController::class, 'preview']);
Route::get('/reports/{id}', [ReportController::class, 'show']);
Route::get('/reports/database/{database}/tables/fields', [ReportController::class, 'getTablesFields']);
Route::post('/reports/query/fields', [ReportController::class, 'getQueryFields']);
Route::post('/reports/excel/fields', [ReportController::class, 'getExcelFields']);
Route::post('/reports/preview', [ReportController::class, 'preview']);
Route::post('/reports/{id}', [ReportController::class, 'show']);
Route::post('/reports', [ReportController::class, 'store']);
Route::put('/reports/{id}', [ReportController::class, 'update']);
Route::delete('/reports/{id}', [ReportController::class, 'destroy']);

Route::get('/onedrive/redirect', [OnedriveController::class, 'redirect']);
Route::get('/onedrive/test', [OnedriveController::class, 'test']);

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/loginsso/{email}/{page}',[App\Http\Controllers\Auth\LoginController::class, 'loginsso'])->name('loginsso');
