<?php

namespace Tests\Unit;

use App\Services\OllamaService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OllamaServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_valid_select_response_is_returned(): void
    {
        Http::fake([
            'localhost:11434/*' => Http::response([
                'response' => 'SELECT SUM(amount) AS total FROM transactions',
            ]),
        ]);

        $service = new OllamaService();
        $result = $service->generateSql('What is the total spend?');

        $this->assertArrayHasKey('sql', $result);
        $this->assertEquals('SELECT SUM(amount) AS total FROM transactions', $result['sql']);
        $this->assertFalse($result['cached']);
    }

    public function test_non_select_response_is_rejected(): void
    {
        Http::fake([
            'localhost:11434/*' => Http::response([
                'response' => 'DROP TABLE transactions',
            ]),
        ]);

        $service = new OllamaService();
        $result = $service->generateSql('Delete everything');

        $this->assertArrayHasKey('error', $result);
        $this->assertArrayNotHasKey('sql', $result);
        $this->assertStringContainsString('unsafe', $result['error']);
    }

    public function test_connection_failure_returns_graceful_error(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        $service = new OllamaService();
        $result = $service->generateSql('What is the total spend?');

        $this->assertArrayHasKey('error', $result);
        $this->assertArrayNotHasKey('sql', $result);
        $this->assertStringContainsString('offline', $result['error']);
    }
}
