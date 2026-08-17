<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExpenseSplit;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $totalYouOwe = ExpenseSplit::where('user_id', $userId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->get()
            ->sum(fn ($split) => $split->amount_owed - $split->amount_paid);

        $totalOwedToYou = ExpenseSplit::whereHas('expense', function ($query) use ($userId) {
                $query->where('paid_by', $userId);
            })
            ->whereIn('status', ['unpaid', 'partial'])
            ->get()
            ->sum(fn ($split) => $split->amount_owed - $split->amount_paid);

        return Inertia::render('Dashboard', [
            'households' => $request->user()->households,
            'totalYouOwe' => round($totalYouOwe, 2),
            'totalOwedToYou' => round($totalOwedToYou, 2),
        ]);
    }
}
