<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDreamRequest;
use App\Http\Requests\UpdateDreamRequest;
use App\Models\Dream;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use App\Jobs\AnalyzeSymbolsJob;


class DreamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dreams = auth()->user()
            ->dreams()
            ->latest('created_at') // change to dream_date once that is figured out
            ->get()
            ->toArray();

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
        $data = $request->validated();
        //format datetime for mysql
        $data['dream_date'] = now()->format('Y-m-d H:i:s');

        $dream = auth()->user()->dreams()

            ->create($data);

        //fire event
        // event(new DreamCreated($dream));

        $webhookUrl = rtrim(env('N8N_URL'), '/') . '/webhook/dream/';

        $payload = [
                'content' => $dream->dream_content,
        ];

        $dreamRequest = Http::post($webhookUrl, $payload);
        
        $dream->update($dreamRequest->json());

        // dd($dreamRequest->json());


          // Dispatch the job for symbol analysis
          AnalyzeSymbolsJob::dispatchSync($dream);


        //return a redirect to the show page of the dream
        return redirect()->route('dreams.show', $dream);
    }

    /**
     * Display the specified resource.
     */
    public function show(Dream $dream)
    {
        $dream->load('symbols');

        return Inertia::render('Dreams/show', ['dream' => $dream]);
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
