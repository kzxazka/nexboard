<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $projects = Project::where('user_id', $user->id)
            ->withCount('tasks')
            ->latest()
            ->get();

        $activeProjects = $projects->whereIn('status', ['in_progress', 'review'])->count();
        $totalProjects = $projects->count();

        $pendingTasks = Task::whereHas('project', fn($q) => $q->where('user_id', $user->id))
            ->whereIn('status', ['todo', 'in_progress'])
            ->count();

        $totalRevenue = Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->sum('amount');

        $totalExpense = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->sum('amount');

        $driver = \DB::connection()->getDriverName();
        $dateExpr = match ($driver) {
            'sqlite' => "strftime('%Y-%m', date)",
            'pgsql' => "TO_CHAR(date, 'YYYY-MM')",
            default => "DATE_FORMAT(date, '%Y-%m')"
        };

        // Monthly revenue for chart (last 6 months)
        $monthlyRevenue = Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->where('date', '>=', now()->subMonths(6)->startOfMonth())
            ->selectRaw("{$dateExpr} as month, SUM(amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $recentProjects = $projects->take(5);

        return Inertia::render('Dashboard', [
            'stats' => [
                'activeProjects' => $activeProjects,
                'totalProjects' => $totalProjects,
                'pendingTasks' => $pendingTasks,
                'totalRevenue' => (float) $totalRevenue,
                'totalExpense' => (float) $totalExpense,
                'netProfit' => (float) ($totalRevenue - $totalExpense),
            ],
            'recentProjects' => $recentProjects,
            'monthlyRevenue' => $monthlyRevenue,
        ]);
    }
}
