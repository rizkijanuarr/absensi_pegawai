<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Presensi;

Route::group(['middleware' => 'auth'], function() {
    Route::get('presensi', Presensi::class)->name('presensi');
});

Route::get('login', function() {
    return redirect('backoffice/login');
})->name('login');

Route::get('/', function () {
    return view('welcome');
});
