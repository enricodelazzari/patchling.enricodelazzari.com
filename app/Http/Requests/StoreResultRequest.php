<?php

namespace App\Http\Requests;

use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreResultRequest extends FormRequest
{
    /**
     * Latin letters only: the share card font has no glyphs for other scripts.
     */
    public const array NAME_RULES = ['string', 'min:2', 'max:20', 'regex:/^[\p{Latin}\p{N} ._\'-]+$/u'];

    /**
     * Get the validation rules that apply to the request.
     *
     * Upper bounds are generous limits on what a real game can produce:
     * they stop hand-crafted submissions, not careful cheaters.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'outcome' => ['required', 'in:completed,crash,users'],
            'months' => ['required', 'integer', 'min:1', 'max:'.Game::TOTAL_MONTHS],
            'revenue' => ['required', 'integer', 'min:0', 'max:2000'],
            'features' => ['required', 'integer', 'min:3', 'max:80'],
            'squashed' => ['required', 'integer', 'min:0', 'max:2000'],
            'breaches' => ['required', 'integer', 'min:0', 'max:20'],
            'health' => ['required', 'integer', 'min:0', 'max:100'],
            'happy' => ['required', 'integer', 'min:0', 'max:100'],
            'debt' => ['required', 'integer', 'min:0', 'max:100'],
            'age' => ['required', 'numeric', 'min:0', 'max:'.Game::TOTAL_MONTHS + 1],
            'name' => ['nullable', ...self::NAME_RULES],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $months = $this->integer('months');

                if ($this->input('outcome') === 'completed' && $months !== Game::TOTAL_MONTHS) {
                    $validator->errors()->add('months', 'A completed game lasts '.Game::TOTAL_MONTHS.' months.');
                }

                /** @var Game $game */
                $game = $this->route('game');

                if ($game->created_at->diffInSeconds(now()) < $game->minimumSecondsFor($months)) {
                    $validator->errors()->add('months', 'This game was played faster than time allows.');
                }
            },
        ];
    }
}
