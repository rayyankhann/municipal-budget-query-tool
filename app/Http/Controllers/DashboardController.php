<?php

namespace App\Http\Controllers;

use App\Models\BudgetCategory;
use App\Models\Department;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->role === 'admin';

        if ($isAdmin) {
            $departments = Department::all();
            $departmentIds = $departments->pluck('id');
        } else {
            $departments = Department::where('id', $user->department_id)->get();
            $departmentIds = [$user->department_id];
        }

        $totalAllocated = BudgetCategory::whereIn('department_id', $departmentIds)
            ->sum('allocated_amount');

        $totalSpent = Transaction::whereIn('department_id', $departmentIds)
            ->sum('amount');

        $percentUsed = $totalAllocated > 0 ? round(($totalSpent / $totalAllocated) * 100, 1) : 0;

        // Spend by department (admin) or by category (dept head)
        if ($isAdmin) {
            $spendByGroup = Department::whereIn('departments.id', $departmentIds)
                ->join('transactions', 'departments.id', '=', 'transactions.department_id')
                ->selectRaw('departments.name as label, SUM(transactions.amount) as total')
                ->groupBy('departments.name')
                ->orderByDesc('total')
                ->get();
        } else {
            $spendByGroup = BudgetCategory::where('budget_categories.department_id', $user->department_id)
                ->join('transactions', 'budget_categories.id', '=', 'transactions.budget_category_id')
                ->selectRaw('budget_categories.name as label, SUM(transactions.amount) as total')
                ->groupBy('budget_categories.name')
                ->orderByDesc('total')
                ->get();
        }

        // Quarterly spend trend
        $quarterlySpend = Transaction::whereIn('department_id', $departmentIds)
            ->selectRaw('quarter, SUM(amount) as total')
            ->groupBy('quarter')
            ->orderBy('quarter')
            ->get();

        // Top vendors by spend
        $topVendors = Transaction::whereIn('department_id', $departmentIds)
            ->whereNotNull('vendor')
            ->selectRaw('vendor, SUM(amount) as total')
            ->groupBy('vendor')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $departmentName = $isAdmin ? 'All Departments' : $departments->first()?->name;

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalAllocated' => round($totalAllocated, 2),
                'totalSpent' => round($totalSpent, 2),
                'percentUsed' => $percentUsed,
            ],
            'spendByGroup' => $spendByGroup,
            'quarterlySpend' => $quarterlySpend,
            'topVendors' => $topVendors,
            'userRole' => $user->role,
            'departmentName' => $departmentName,
            'isAdmin' => $isAdmin,
        ]);
    }
}
