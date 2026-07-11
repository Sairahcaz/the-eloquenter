<?php

namespace App\Http\Requests;

use App\Game\Levels;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreHighscoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:30'],
            'stars' => ['required', 'integer', 'min:0', 'max:'.count(Levels::all()) * 3],
        ];
    }
}
