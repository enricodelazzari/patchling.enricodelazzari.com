<?php

namespace App\Http\Controllers;

use App\Enums\Verdict;
use App\Http\Requests\StoreResultRequest;
use App\Http\Requests\UpdateResultRequest;
use App\Http\Resources\ResultResource;
use App\Models\Game;
use App\Models\Result;
use App\Support\ResultCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResultController extends Controller
{
    /**
     * Record the outcome of a game. The score is computed here, never by the client.
     */
    public function store(StoreResultRequest $request, Game $game): JsonResponse
    {
        abort_if($game->result()->exists(), 409, 'This game already has a result.');

        $data = $request->validated();
        $verdict = Verdict::fromStats($data['outcome'], $data['health'], $data['happy'], (float) $data['age'], $data['debt'], $data['breaches']);
        $token = Str::random(40);

        $result = $game->result()->create([
            ...collect($data)->only(['name', 'months', 'revenue', 'features', 'squashed', 'breaches', 'health', 'happy']),
            'verdict' => $verdict,
            'score' => Result::scoreFor($verdict, $data['months'], $data['revenue'], $data['health'], $data['happy'], $data['breaches']),
            'claim_token' => hash('sha256', $token),
        ]);

        return (new ResultResource($result))
            ->additional(['claim_token' => $token])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Put an anonymous result on the leaderboard under a name.
     */
    public function update(UpdateResultRequest $request, Result $result): ResultResource
    {
        abort_if($result->name !== null, 409, 'This result already has a name.');

        $result->update(['name' => $request->validated('name')]);

        return new ResultResource($result);
    }

    /**
     * Public page for a shared result, with Open Graph tags for link previews.
     */
    public function show(Result $result): View
    {
        return view('result', ['result' => $result, 'rank' => $result->position()]);
    }

    /**
     * The image shown in social link previews.
     */
    public function image(Result $result, ResultCard $card): Response
    {
        return response($card->render($result, $result->position()), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
