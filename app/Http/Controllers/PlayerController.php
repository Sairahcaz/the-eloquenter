<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlayerRequest;
use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class PlayerController extends Controller
{
    public function store(StorePlayerRequest $request): RedirectResponse
    {
        $player = null;

        if ($token = $request->validated('token')) {
            $player = Player::query()->where('token', $token)->first();
        }

        $player ??= Player::create([
            'name' => $request->string('name')->trim()->value(),
            'token' => Str::uuid()->toString(),
        ]);

        $request->session()->put('player_id', $player->id);

        return back();
    }
}
