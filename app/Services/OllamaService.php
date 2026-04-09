<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    private string $baseUrl = 'http://localhost:11434';
    private string $model = 'llama3.2';

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

    public function generateSql(string $question): array
    {
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
- When asked about budget or allocated, query the budget_categories allocated_amount column
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

            // Clean up common LLM artifacts
            $sql = preg_replace('/^```sql?\s*/i', '', $sql);
            $sql = preg_replace('/\s*```$/', '', $sql);
            $sql = trim($sql, " \t\n\r\0\x0B;");

            // Validate it starts with SELECT
            if (!preg_match('/^\s*SELECT/i', $sql)) {
                return ['error' => 'The AI generated an invalid query. Please rephrase your question.'];
            }

            // Block dangerous statements
            if (preg_match('/\b(DROP|DELETE|UPDATE|INSERT|ALTER|CREATE|TRUNCATE|EXEC|EXECUTE)\b/i', $sql)) {
                return ['error' => 'The generated query contains disallowed operations.'];
            }

            return ['sql' => $sql];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Ollama connection failed: ' . $e->getMessage());
            return ['error' => 'Cannot connect to Ollama. Please make sure Ollama is running (ollama serve) and the llama3.2 model is pulled.'];
        } catch (\Exception $e) {
            Log::error('OllamaService error: ' . $e->getMessage());
            return ['error' => 'An unexpected error occurred while generating the query.'];
        }
    }
}
