<?php

namespace App\Http\Controllers;

use App\Http\Resources\ResultResource;
use App\Models\Result;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeaderboardController extends Controller
{
    public const int SIZE = 20;

    public function __invoke(): AnonymousResourceCollection
    {
        $results = Result::ranked()->limit(self::SIZE)->get()
            ->each(fn (Result $result, int $index) => $result->rank = $index + 1);

        return ResultResource::collection($results);
    }
}
