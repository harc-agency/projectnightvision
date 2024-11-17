<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDreamRequest;
use App\Http\Requests\UpdateDreamRequest;
use App\Models\Dream;
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
        dd(
            $request->validated()
        );
        //save dream
        $dream = auth()->user()->dreams()->create($request->validated());

        //fire off an event to process the dream
        // event(new DreamWasCreated($dream));

        return redirect()->route('dreams.index')->with('success', 'Dream created successfully');
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
