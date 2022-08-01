<?php

use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', [MainController::class, 'index']);
Route::get('/generate-table', [MainController::class, 'indexTable']);
Route::get('/generate-info', [MainController::class, 'indexInfographic']);
Route::get('/test', [MainController::class, 'generateInfographic']);

Route::get('/upload', [MainController::class, 'showUpload']);
Route::post('/generate-text', [MainController::class, 'generateText']);
Route::post('/generate-table', [MainController::class, 'generateTable']);
Route::post('/generate-info', [MainController::class, 'generateInfographic']);

Route::post('/upload', [MainController::class, 'upload']);
