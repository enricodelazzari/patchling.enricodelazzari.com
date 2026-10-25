<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResultRequest extends FormRequest
{
    /**
     * Only the player holding the claim token can name a result.
     */
    public function authorize(): bool
    {
        return $this->route('result')->ownedBy($this->string('claim_token'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'claim_token' => ['required', 'string'],
            'name' => ['required', ...StoreResultRequest::NAME_RULES],
        ];
    }
}
