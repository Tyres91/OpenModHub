<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRatingRequest;
use App\Models\Mod;
use Illuminate\Http\RedirectResponse;

class RatingController extends Controller
{
    public function store(StoreRatingRequest $request, Mod $mod): RedirectResponse
    {
        $request->user()->ratings()->updateOrCreate(
            ['mod_id' => $mod->id],
            ['score' => $request->integer('score')],
        );

        return back()->with('status', 'Rating saved.');
    }
}
