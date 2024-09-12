<?php

namespace App\Http\Controllers;

use App\Events\GameJoined;
use App\Events\PlayerMadeMove;
use App\Http\Requests\StoreGameRequest;
use App\Http\Requests\UpdateGameRequest;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return inertia('Dashboard', [
            'games' =>  Game::with(['playerOne'])
                        ->whereNull('player_two_id')
                        ->where('player_one_id', '!=', $request->user()->id)
                        ->oldest()
                        ->simplePaginate(100)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGameRequest $request)
    {
        $game   =   Game::create(['player_one_id'=>$request->user()->id]);
        if ($game) {
            return to_route('games.show', $game);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Game $game)
    {
        $game->load(['playerOne', 'playerTwo']);
        return inertia('Games/Show', ['game'=>$game]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Game $game)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGameRequest $request, Game $game)
    {

        $game->update($request->validated());

        broadcast(new PlayerMadeMove($game))->toOthers();

        return to_route('games.show', ['game'=>$game]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Game $game)
    {
        //
    }

    public function joinGame(Request $request, Game $game)
    {

        Gate::authorize('joinGame', $game);

        GameJoined::dispatch($game);
        $game->update(['player_two_id'  => $request->user()->id]);

        return to_route('games.show', $game);
    }
}
