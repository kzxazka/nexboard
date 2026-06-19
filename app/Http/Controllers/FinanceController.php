<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::where('user_id', Auth::id())
            ->with('project:id,name');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->filled('from')) {
            $query->where('date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('date', '<=', $request->to);
        }

        $transactions = $query->latest('date')->paginate(20);

        // Summary stats
        $totalIncome = Transaction::where('user_id', Auth::id())
            ->where('type', 'income')
            ->sum('amount');

        $totalExpense = Transaction::where('user_id', Auth::id())
            ->where('type', 'expense')
            ->sum('amount');

        $driver = \DB::connection()->getDriverName();
        $dateExpr = match ($driver) {
            'sqlite' => "strftime('%Y-%m', date)",
            'pgsql' => "TO_CHAR(date, 'YYYY-MM')",
            default => "DATE_FORMAT(date, '%Y-%m')"
        };

        // Monthly trend (last 12 months)
        $monthlyTrend = Transaction::where('user_id', Auth::id())
            ->where('date', '>=', now()->subMonths(12)->startOfMonth())
            ->selectRaw("{$dateExpr} as month, type, SUM(amount) as total")
            ->groupBy('month', 'type')
            ->orderBy('month')
            ->get()
            ->groupBy('month')
            ->map(fn($items) => [
                'income' => (float) $items->where('type', 'income')->sum('total'),
                'expense' => (float) $items->where('type', 'expense')->sum('total'),
            ])
            ->toArray();

        return Inertia::render('Finance/Index', [
            'transactions' => $transactions,
            'summary' => [
                'totalIncome' => (float) $totalIncome,
                'totalExpense' => (float) $totalExpense,
                'netProfit' => (float) ($totalIncome - $totalExpense),
            ],
            'monthlyTrend' => $monthlyTrend,
            'filters' => $request->only(['type', 'from', 'to']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'project_id' => 'nullable|exists:projects,id',
            'date' => 'required|date',
        ]);

        $validated['user_id'] = Auth::id();

        Transaction::create($validated);

        return redirect()->back()->with('success', 'Transaction recorded successfully.');
    }

    public function update(Request $request, Transaction $transaction)
    {
        Gate::authorize('update', $transaction);

        $validated = $request->validate([
            'type' => 'sometimes|in:income,expense',
            'amount' => 'sometimes|numeric|min:0.01',
            'description' => 'sometimes|string|max:255',
            'category' => 'nullable|string|max:100',
            'project_id' => 'nullable|exists:projects,id',
            'date' => 'sometimes|date',
        ]);

        $transaction->update($validated);

        return redirect()->back()->with('success', 'Transaction updated successfully.');
    }

    public function destroy(Transaction $transaction)
    {
        Gate::authorize('delete', $transaction);

        $transaction->delete();

        return redirect()->back()->with('success', 'Transaction deleted successfully.');
    }
}
