<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\JsonResponse;

class GameController extends Controller
{
    /**
     * Start the clock on a new game.
     */
    public function store(): JsonResponse
    {
        return response()->json(['id' => Game::create()->id], 201);
    }
}
