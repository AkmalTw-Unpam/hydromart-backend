<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Trik Legendaris: Jika rute dianggap sebagai folder oleh server hosting, 
// paksa Laravel untuk menangkap dan memprosesnya secara internal.
Route::fallback(function () {
    return response()->json([
        'message' => 'Jalur dialihkan otomatis oleh Laravel Cloud',
        'status' => 'success'
    ], 200);
});