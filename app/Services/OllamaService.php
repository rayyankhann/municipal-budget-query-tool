<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    private string $baseUrl = 'http://localhost:11434';
    private string $model = 'llama3.2';

    /**
     * Database schema context passed to the LLM so it understands the table
     * structure and can generate valid JOINs and WHERE clauses.
     */
    private string $schemaContext = <<<'SCHEMA'
Tables:
- departments(id, name, code, head_name)
- budget_categories(id, department_id, name, fiscal_year, allocated_amount)
- transactions(id, department_id, budget_category_id, description, amount, vendor, transaction_date, quarter)
- users(id, name, email, role, department_id)

Relationships:
- transactions.department_id -> departments.id
- transactions.budget_category_id -> budget_categories.id
- budget_categories.department_id -> departments.id
- users.department_id -> departments.id
SCHEMA;

    /**
     * Convert a natural language question into a safe SELECT-only SQL query.
     *
     * @param string   $question     The user's plain-English budget question
     * @param int|null $departmentId If set, the LLM is instructed to scope all
     *                               results to this department (for dept heads)
     * @return array{sql?: string, cached?: bool, error?: string}
     */
    public function generateSql(string $question, ?int $departmentId = null): array
    {
        // Cache key includes the department scope so admin and dept-head queries
        // for the same question produce separate cached entries.
        $cacheKey = 'ollama_sql_' . md5(strtolower(trim($question)) . '_dept_' . ($departmentId ?? 'all'));
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return ['sql' => $cached, 'cached' => true];
        }

        // When a department_id is provided, inject a mandatory filter rule into
        // the system prompt so the LLM generates the WHERE clause itself (rather
        // than us trying to patch it into arbitrary SQL after the fact).
        $departmentRule = '';
        if ($departmentId) {
            $departmentRule = "\n- IMPORTANT: This user can ONLY see data for department_id = {$departmentId}. You MUST add a WHERE condition filtering by department_id = {$departmentId} on the appropriate table (transactions.department_id, budget_categories.department_id, or departments.id depending on which tables are in the query). Never return data from other departments.";
        }

        // System prompt: gives the LLM full schema context plus strict rules
        // to output only a raw SELECT query — no markdown, no explanation.
        $systemPrompt = <<<PROMPT
You are a SQL query generator for a municipal budget SQLite database.

{$this->schemaContext}

Rules:
- Respond with ONLY a valid SQLite SELECT query
- No markdown, no explanation, no code fences, no semicolons
- Only SELECT statements — never DROP, DELETE, UPDATE, INSERT, ALTER, CREATE
- Use proper JOINs when querying across tables
- Use aggregation functions (SUM, COUNT, AVG) when appropriate
- For quarter references: quarter column is 1-4
- For fiscal year data, use fiscal_year column in budget_categories
- Always alias calculated columns with readable names
- When asked about spending or spend, query the transactions table amount column
- When asked about budget or allocated, query the budget_categories allocated_amount column{$departmentRule}
PROMPT;

        try {
            $response = Http::timeout(60)->post("{$this->baseUrl}/api/generate", [
                'model' => $this->model,
                'prompt' => $question,
                'system' => $systemPrompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.1,
                ],
            ]);

            if (!$response->successful()) {
                return ['error' => 'Ollama returned an error. Make sure Ollama is running.'];
            }

            $sql = trim($response->json('response', ''));

            // Strip markdown code fences and trailing semicolons that LLMs
            // sometimes add despite instructions not to.
            $sql = preg_replace('/^```sql?\s*/i', '', $sql);
            $sql = preg_replace('/\s*```$/', '', $sql);
            $sql = trim($sql, " \t\n\r\0\x0B;");

            // SELECT-only validation: the query must begin with SELECT.
            // This is the primary safety gate — anything else is rejected.
            if (!preg_match('/^\s*SELECT/i', $sql)) {
                return ['error' => 'The AI returned an unsafe query. Please rephrase your question.'];
            }

            // Secondary blocklist: reject if any destructive keyword appears
            // anywhere in the query (even inside a subquery or CTE).
            if (preg_match('/\b(DROP|DELETE|UPDATE|INSERT|ALTER|CREATE|TRUNCATE|EXEC|EXECUTE)\b/i', $sql)) {
                return ['error' => 'The AI returned an unsafe query. Please rephrase your question.'];
            }

            // Cache the generated SQL for 1 hour to avoid redundant LLM calls
            // for repeated questions. The cache is keyed by question + dept scope.
            Cache::put($cacheKey, $sql, 3600);

            return ['sql' => $sql, 'cached' => false];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Ollama is not running or unreachable on localhost:11434.
            Log::warning('Ollama connection failed: ' . $e->getMessage());
            return ['error' => 'AI query engine is offline. Make sure Ollama is running locally with: ollama serve'];
        } catch (\Exception $e) {
            Log::error('OllamaService error: ' . $e->getMessage());
            return ['error' => 'An unexpected error occurred while generating the query.'];
        }
    }
}
