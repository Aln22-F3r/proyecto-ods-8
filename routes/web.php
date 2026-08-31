<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/inicio', 'inicio');
Route::view('/admin', 'administrador');
Route::view('/cliente', 'cliente');
