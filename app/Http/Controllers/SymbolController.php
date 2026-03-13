<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSymbolRequest;
use App\Http\Requests\UpdateSymbolRequest;
use App\Models\Dream;
use App\Models\Symbol;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        $linkedDreamsQuery = $this->visibleLinkedDreamsQuery($symbol);
        $linkedDreamsCount = (clone $linkedDreamsQuery)->distinct('dreams.id')->count('dreams.id');
        $linkedDreams = (clone $linkedDreamsQuery)
            ->latest('dreams.created_at')
            ->limit(8)
            ->get(['dreams.id', 'dreams.title', 'dreams.dream_date', 'dreams.sentiment']);

        if ($linkedDreamsCount === 0) {
            $fallbackDreamsQuery = $this->fallbackDreamsQuery($symbol);

            $linkedDreamsCount = (clone $fallbackDreamsQuery)->count('dreams.id');
            $linkedDreams = (clone $fallbackDreamsQuery)
                ->latest('dreams.created_at')
                ->limit(8)
                ->get(['dreams.id', 'dreams.title', 'dreams.dream_date', 'dreams.sentiment']);
        }

        $symbol->setRelation('dreams', $linkedDreams);
        $symbol->setAttribute('dreams_count', $linkedDreamsCount);

        return Inertia::render('Symbols/Show', [
            'symbol' => $symbol
        ]);
    }

    protected function visibleLinkedDreamsQuery(Symbol $symbol): BelongsToMany
    {
        return $symbol->dreams()
            ->where(function (Builder $query) {
                $query->where('dreams.user_id', auth()->id())
                    ->orWhere('dreams.is_public', true);
            });
    }

    protected function fallbackDreamsQuery(Symbol $symbol): Builder
    {
        $phrases = collect([
            trim((string) $symbol->title),
            trim(str_replace(['_', '-'], ' ', (string) $symbol->symbol_key)),
        ])
            ->filter()
            ->unique()
            ->values();

        return Dream::query()
            ->where(function (Builder $query) {
                $query->where('user_id', auth()->id())
                    ->orWhere('is_public', true);
            })
            ->where(function (Builder $query) use ($phrases) {
                foreach ($phrases as $phrase) {
                    $query->orWhere('title', 'like', '%' . $phrase . '%')
                        ->orWhere('dream_content', 'like', '%' . $phrase . '%');
                }
            });
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
