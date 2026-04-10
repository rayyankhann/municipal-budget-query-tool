<?php

namespace App\Http\Controllers;

use App\Models\BudgetCategory;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BudgetAlertController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->role === 'admin';

        $query = BudgetCategory::query()
            ->join('departments', 'budget_categories.department_id', '=', 'departments.id')
            ->leftJoin('transactions', 'budget_categories.id', '=', 'transactions.budget_category_id')
            ->select(
                'budget_categories.id',
                'budget_categories.name as category',
                'departments.name as department',
                'budget_categories.allocated_amount',
                DB::raw('COALESCE(SUM(transactions.amount), 0) as total_spent'),
                DB::raw('ROUND(COALESCE(SUM(transactions.amount), 0) / budget_categories.allocated_amount * 100, 1) as percent_used')
            )
            ->groupBy('budget_categories.id', 'budget_categories.name', 'departments.name', 'budget_categories.allocated_amount');

        if (!$isAdmin) {
            $query->where('budget_categories.department_id', $user->department_id);
        }

        $alerts = $query->orderByDesc('percent_used')->get();

        $overBudget = $alerts->filter(fn($a) => $a->percent_used > 100)->count();
        $nearBudget = $alerts->filter(fn($a) => $a->percent_used >= 90 && $a->percent_used <= 100)->count();
        $healthy = $alerts->filter(fn($a) => $a->percent_used < 90)->count();

        $departments = $isAdmin ? Department::orderBy('name')->pluck('name') : [];

        return Inertia::render('BudgetAlerts', [
            'alerts' => $alerts,
            'summary' => [
                'overBudget' => $overBudget,
                'nearBudget' => $nearBudget,
                'healthy' => $healthy,
            ],
            'isAdmin' => $isAdmin,
            'departments' => $departments,
        ]);
    }
}
