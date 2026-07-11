<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\LevelPlayController;
use App\Http\Controllers\PlayerController;
use Illuminate\Support\Facades\Route;

Route::get('/', GameController::class)->name('home');

Route::post('/players', [PlayerController::class, 'store'])->name('players.store');

Route::prefix('/levels/{levelId}')->name('levels.')->middleware('throttle:120,1')->group(function () {
    Route::post('/attempt', [LevelPlayController::class, 'start'])->name('attempt');
    Route::post('/connections', [LevelPlayController::class, 'connect'])->name('connections');
    Route::post('/guess', [LevelPlayController::class, 'guess'])->name('guess');
    Route::post('/code', [LevelPlayController::class, 'code'])->name('code');
    Route::post('/hint', [LevelPlayController::class, 'hint'])->name('hint');
});
