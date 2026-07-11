<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHighscoreRequest;
use App\Models\Highscore;
use Illuminate\Http\RedirectResponse;

class HighscoreController extends Controller
{
    public function store(StoreHighscoreRequest $request): RedirectResponse
    {
        $highscore = Highscore::firstOrNew(['name' => $request->string('name')->trim()->value()]);
        $highscore->stars = max($highscore->stars ?? 0, $request->integer('stars'));
        $highscore->save();

        return back();
    }
}
