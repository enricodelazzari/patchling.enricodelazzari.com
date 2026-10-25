<?php

use App\Http\Controllers\ResultController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'game')->name('home');
Route::get('r/{result}', [ResultController::class, 'show'])->name('results.show');
Route::get('r/{result}/card.png', [ResultController::class, 'image'])->name('results.image');
