<?php

namespace App\Http\Requests;

use App\Models\Player;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePlayerRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:30',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->resumesExistingPlayer()) {
                        return;
                    }

                    $nameTaken = Player::query()
                        ->whereRaw('lower(name) = ?', [mb_strtolower(trim((string) $value))])
                        ->exists();

                    if ($nameTaken) {
                        $fail('This name is already taken. Pick another one.');
                    }
                },
            ],
            'token' => ['nullable', 'uuid'],
        ];
    }

    /**
     * A valid token resumes that player and the submitted name is ignored,
     * so it must not trip the uniqueness check.
     */
    private function resumesExistingPlayer(): bool
    {
        $token = $this->input('token');

        return is_string($token)
            && $token !== ''
            && Player::query()->where('token', $token)->exists();
    }
}
