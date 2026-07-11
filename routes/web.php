<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\HighscoreController;
use Illuminate\Support\Facades\Route;

Route::get('/', GameController::class)->name('home');
Route::post('/highscores', [HighscoreController::class, 'store'])->name('highscores.store');
