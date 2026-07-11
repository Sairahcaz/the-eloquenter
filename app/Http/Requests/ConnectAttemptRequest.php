<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConnectAttemptRequest extends FormRequest
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
            'from' => ['required', 'array'],
            'from.table' => ['required', 'string', 'max:64'],
            'from.column' => ['required', 'string', 'max:64'],
            'to' => ['required', 'array'],
            'to.table' => ['required', 'string', 'max:64'],
            'to.column' => ['required', 'string', 'max:64'],
        ];
    }
}
