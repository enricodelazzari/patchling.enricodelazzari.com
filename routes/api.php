<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ResultController;
use Illuminate\Support\Facades\Route;

Route::post('games', [GameController::class, 'store'])->middleware('throttle:games');
Route::post('games/{game}/result', [ResultController::class, 'store'])->middleware('throttle:results');
Route::patch('results/{result}', [ResultController::class, 'update'])->middleware('throttle:results');
Route::get('leaderboard', LeaderboardController::class);
