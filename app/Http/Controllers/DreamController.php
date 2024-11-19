<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDreamRequest;
use App\Http\Requests\UpdateDreamRequest;
use App\Models\Dream;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class DreamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dreams = auth()->user()->dreams()->latest('dream_date')->get()->toArray();

        return Inertia::render('Dreams/index', ['dreams' => $dreams]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Dreams/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDreamRequest $request) // StoreDreamRequest $request
    {

        //save dream
        $dream = auth()->user()->dreams()->create($request->validated());

        //fire event
        // event(new DreamCreated($dream));

        $dreamRequest = Http::post(env('N8N_URL').'/webhook/dream', ['content' => $dream->dream_content]);
        // $symbolRequest = Http::post(env('N8N_URL').'/webhook/symbol', ['content' => $dream->dream_content]);
        $dream->update($request->json());

        //return a redirect to the show page of the dream
        return redirect()->route('dreams.show', $dream);
    }

    /**
     * Display the specified resource.
     */
    public function show(Dream $dream)
    {
        // Add your logic here if needed
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Dream $dream)
    {
        // Add your logic here if needed
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDreamRequest $request, Dream $dream)
    {
        // Add your logic here if needed
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dream $dream)
    {
        // Add your logic here if needed
    }
}
