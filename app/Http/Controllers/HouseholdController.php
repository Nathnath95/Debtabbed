<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Inertia\Inertia;

use App\Http\Controllers\Controller;
use App\Models\Household;

class HouseholdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('Households/Index', [
            'households' => $request->user()->households,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Households/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $household = Household::create([
            'name' => $validated['name'],
            'created_by' => $request->user()->id,
            'invite_code' => \Illuminate\Support\Str::random(8),
        ]);

        $household->users()->attach($request->user()->id);
        
        return redirect()->route('households.show', $household);
    }

    /**
     * Display the specified resource.
     */
    public function show(Household $household)
    {
        return Inertia::render('Households/Show', [
            'household' => $household->load('users'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    } 

    public function showJoin(string $code)
    {
        $household = Household::where('invite_code', $code)->firstOrFail();

        return Inertia::render('Households/Join', [
            'household' => $household,
            'code' => $code,
        ]);
    }

    public function join(Request $request, string $code)
    {
        $household = Household::where('invite_code', $code)->firstOrFail();

        if (! $household->users->contains($request->user()->id)) {
            $household->users()->attach($request->user()->id);
        }

        return redirect()->route('households.show', $household);
    }
}
