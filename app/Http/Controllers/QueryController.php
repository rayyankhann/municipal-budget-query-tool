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

        // Pass department_id to Ollama so it generates filtered SQL directly
        $departmentId = ($user->role === 'department_head' && $user->department_id)
            ? $user->department_id
            : null;

        $ollama = new OllamaService();
        $result = $ollama->generateSql($request->input('question'), $departmentId);

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

            \Illuminate\Support\Facades\Log::error('Budget query failed', ['sql' => $sql, 'error' => $e->getMessage()]);

            return response()->json([
                'error' => 'The generated query could not be executed. Please try rephrasing your question.',
                'sql' => $sql,
                'question' => $request->input('question'),
            ]);
        }
    }

    public function suggestions()
    {
        return response()->json([
            'suggestions' => [
                'What is the total amount spent by each department?',
                'Show the top 10 largest transactions with department and vendor names',
                'Which budget categories have spent more than their allocated amount?',
                'What is the total spend per quarter for the Police Department?',
                'List all vendors and how much we paid them in total, sorted highest first',
                'What is the average transaction amount by department?',
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

}
