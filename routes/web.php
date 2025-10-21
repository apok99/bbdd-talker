<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ChatController::class, 'index'])->name('chat.index');
Route::post('/enviar', [ChatController::class, 'send'])->name('chat.send');
Route::post('/reiniciar', [ChatController::class, 'reset'])->name('chat.reset');
