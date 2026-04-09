<?php

namespace App\Http\Controllers;

use App\Models\QueryLog;
use App\Services\OllamaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QueryController extends Controller
{
    public function index(Request $request)
    {
        $history = QueryLog::where('user_id', $request->user()->id)
            ->where('success', true)
            ->latest()
            ->limit(20)
            ->get(['id', 'question', 'result_count', 'execution_time_ms', 'created_at']);

        return Inertia::render('Query', [
            'history' => $history,
        ]);
    }

    public function query(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $startTime = microtime(true);

        $ollama = new OllamaService();
        $result = $ollama->generateSql($request->input('question'));

        if (isset($result['error'])) {
            QueryLog::create([
                'user_id' => $user->id,
                'question' => $request->input('question'),
                'success' => false,
                'error_message' => $result['error'],
                'execution_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
            ]);

            return response()->json([
                'error' => $result['error'],
                'question' => $request->input('question'),
            ]);
        }

        $sql = $result['sql'];
        $cached = $result['cached'] ?? false;

        // For department_head users, inject department filter
        if ($user->role === 'department_head' && $user->department_id) {
            $sql = $this->injectDepartmentFilter($sql, $user->department_id);
        }

        try {
            $results = DB::select($sql);
            $columns = !empty($results) ? array_keys((array) $results[0]) : [];
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            QueryLog::create([
                'user_id' => $user->id,
                'question' => $request->input('question'),
                'generated_sql' => $sql,
                'result_count' => count($results),
                'success' => true,
                'execution_time_ms' => $executionTime,
            ]);

            return response()->json([
                'sql' => $sql,
                'results' => array_map(fn($row) => (array) $row, $results),
                'columns' => $columns,
                'question' => $request->input('question'),
                'rowCount' => count($results),
                'cached' => $cached,
                'executionTime' => $executionTime,
            ]);
        } catch (\Exception $e) {
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            QueryLog::create([
                'user_id' => $user->id,
                'question' => $request->input('question'),
                'generated_sql' => $sql,
                'success' => false,
                'error_message' => $e->getMessage(),
                'execution_time_ms' => $executionTime,
            ]);

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

    public function export(Request $request)
    {
        $request->validate([
            'columns' => 'required|string',
            'results' => 'required|string',
        ]);

        $columns = json_decode($request->input('columns'), true);
        $results = json_decode($request->input('results'), true);

        return new StreamedResponse(function () use ($columns, $results) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);
            foreach ($results as $row) {
                $line = [];
                foreach ($columns as $col) {
                    $line[] = $row[$col] ?? '';
                }
                fputcsv($handle, $line);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="budget-query-results.csv"',
        ]);
    }

    public function history(Request $request)
    {
        $history = QueryLog::where('user_id', $request->user()->id)
            ->where('success', true)
            ->latest()
            ->limit(20)
            ->get(['id', 'question', 'result_count', 'execution_time_ms', 'created_at']);

        return response()->json(['history' => $history]);
    }

    private function injectDepartmentFilter(string $sql, int $departmentId): string
    {
        if (preg_match('/\bWHERE\b/i', $sql)) {
            $sql = preg_replace(
                '/\bWHERE\b/i',
                "WHERE (transactions.department_id = {$departmentId} OR budget_categories.department_id = {$departmentId} OR departments.id = {$departmentId}) AND",
                $sql,
                1
            );
        } else {
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
