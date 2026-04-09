<?php

namespace App\Http\Controllers;

use App\Services\OllamaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QueryController extends Controller
{
    public function index()
    {
        return Inertia::render('Query');
    }

    public function query(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $ollama = new OllamaService();
        $result = $ollama->generateSql($request->input('question'));

        if (isset($result['error'])) {
            return response()->json([
                'error' => $result['error'],
                'question' => $request->input('question'),
            ]);
        }

        $sql = $result['sql'];

        // For department_head users, inject department filter
        if ($user->role === 'department_head' && $user->department_id) {
            $sql = $this->injectDepartmentFilter($sql, $user->department_id);
        }

        try {
            $results = DB::select($sql);
            $columns = !empty($results) ? array_keys((array) $results[0]) : [];

            return response()->json([
                'sql' => $sql,
                'results' => array_map(fn($row) => (array) $row, $results),
                'columns' => $columns,
                'question' => $request->input('question'),
                'rowCount' => count($results),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Query execution failed: ' . $e->getMessage(),
                'sql' => $sql,
                'question' => $request->input('question'),
            ]);
        }
    }

    public function suggestions()
    {
        return response()->json([
            'suggestions' => [
                'How much did we spend on contractors in Q3?',
                'Which department has the highest total spend?',
                'Show all transactions over $10,000',
                'What is the total spend by vendor?',
                'Which budget categories are over their allocated amount?',
                'Show spending trends by quarter for each department',
            ],
        ]);
    }

    private function injectDepartmentFilter(string $sql, int $departmentId): string
    {
        // If the query already has a WHERE clause, append AND
        if (preg_match('/\bWHERE\b/i', $sql)) {
            // Insert department filter after the first WHERE
            $sql = preg_replace(
                '/\bWHERE\b/i',
                "WHERE (transactions.department_id = {$departmentId} OR budget_categories.department_id = {$departmentId} OR departments.id = {$departmentId}) AND",
                $sql,
                1
            );
        } else {
            // Try to insert before GROUP BY, ORDER BY, or LIMIT
            if (preg_match('/\b(GROUP BY|ORDER BY|LIMIT)\b/i', $sql, $matches, PREG_OFFSET_MATCH)) {
                $pos = $matches[0][1];
                $sql = substr($sql, 0, $pos) .
                    "WHERE (transactions.department_id = {$departmentId} OR budget_categories.department_id = {$departmentId} OR departments.id = {$departmentId}) " .
                    substr($sql, $pos);
            } else {
                $sql .= " WHERE (transactions.department_id = {$departmentId} OR budget_categories.department_id = {$departmentId} OR departments.id = {$departmentId})";
            }
        }

        return $sql;
    }
}
