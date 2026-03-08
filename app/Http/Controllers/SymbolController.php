<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSymbolRequest;
use App\Http\Requests\UpdateSymbolRequest;
use App\Models\Symbol;
use Inertia\Inertia;

class SymbolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $symbols = Symbol::all();
        return Inertia::render('Symbols/Index', [
            'symbols' => $symbols
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Symbols/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSymbolRequest $request)
    {
    }

    /**
     * Display the specified resource.
     */
    public function show(Symbol $symbol)
    {
        $symbol->loadCount('dreams');
        $symbol->load([
            'dreams' => fn ($query) => $query
                ->latest('dreams.created_at')
                ->limit(8)
                ->select('dreams.id', 'dreams.title', 'dreams.dream_date', 'dreams.sentiment'),
        ]);

        return Inertia::render('Symbols/Show', [
            'symbol' => $symbol
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Symbol $symbol)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSymbolRequest $request, Symbol $symbol)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Symbol $symbol)
    {
        //
    }
}
