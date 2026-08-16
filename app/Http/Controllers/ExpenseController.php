<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Household;

use Inertia\Inertia;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Household $household)
    {
        return Inertia::render('Expenses/Create', [
            'household' => $household->load('users'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Household $household)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'payment_method' => 'required|string|max:255',
            'proof' => 'nullable|file|image|max:5120',
            'paid_by' => 'required|exists:users,id',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,id',
            'split_type' => 'required|in:even,custom',
            'splits' => 'required_if:split_type,custom|array',
            'splits.*.user_id' => 'required_if:split_type,custom|exists:users,id',
            'splits.*.percentage' => 'required_if:split_type,custom|numeric|min:0|max:100',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('proofs', 'public');
        }

        $expense = Expense::create([
            'household_id' => $household->id,
            'posted_by' => $request->user()->id,
            'paid_by' => $validated['paid_by'],
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'paid_at' => $validated['paid_at'],
            'payment_method' => $validated['payment_method'],
            'proof_path' => $proofPath,
            'split_type' => $validated['split_type'],
        ]);

        // participant_ids already excludes the payer (frontend guarantees this),
        // but we defensively strip the payer out here too, in case of tampering.
        $participantIds = array_diff($validated['participant_ids'], [$validated['paid_by']]);

        $totalSharers = count($participantIds) + 1; // +1 for the payer's own share

        if ($validated['split_type'] === 'even') {
            $percentagePerPerson = 100 / $totalSharers;

            foreach ($participantIds as $userId) {
                ExpenseSplit::create([
                    'expense_id' => $expense->id,
                    'user_id' => $userId,
                    'percentage' => $percentagePerPerson,
                    'amount_owed' => $expense->amount * ($percentagePerPerson / 100),
                    'amount_paid' => 0,
                    'status' => 'unpaid',
                ]);
            }
        } else {
            $totalPercentage = collect($validated['splits'])->sum('percentage');

            if (round($totalPercentage, 2) !== 100.00) {
                return back()->withErrors(['splits' => 'The total percentage must equal 100%.']);
            }

            foreach ($validated['splits'] as $split) {
                if ($split['user_id'] == $validated['paid_by']) {
                    continue; // the payer's share doesn't create a debt
                }

                if (! in_array($split['user_id'], $participantIds)) {
                    continue; // ignore any split entry for someone not actually a participant
                }

                ExpenseSplit::create([
                    'expense_id' => $expense->id,
                    'user_id' => $split['user_id'],
                    'percentage' => $split['percentage'],
                    'amount_owed' => $expense->amount * ($split['percentage'] / 100),
                    'amount_paid' => 0,
                    'status' => 'unpaid',
                ]);
            }
        }

        return redirect()->route('households.show', $household);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
}
