<?php

namespace SmartCache\Analyzer\Tests\Unit;

use SmartCache\Analyzer\Services\CacheAnalyzer;
use SmartCache\Analyzer\Tests\TestCase;
use SmartCache\Analyzer\Models\QueryAnalysis;

class CacheAnalyzerTest extends TestCase
{
    protected CacheAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = app(CacheAnalyzer::class);
    }

    /** @test */
    public function it_can_get_cache_stats(): void
    {
        $stats = $this->analyzer->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('hit_ratio', $stats);
        $this->assertArrayHasKey('total_hits', $stats);
        $this->assertArrayHasKey('total_misses', $stats);
        $this->assertArrayHasKey('total_requests', $stats);
    }

    /** @test */
    public function it_can_analyze_queries(): void
    {
        $sql = 'SELECT * FROM users WHERE id = ?';
        $time = 150.5;

        $this->analyzer->analyzeQuery($sql, $time);

        $this->assertDatabaseHas('smart_cache_query_analyses', [
            'query_hash' => md5($sql),
            'query' => $sql,
        ]);
    }

    /** @test */
    public function it_can_generate_recommendations(): void
    {
        // Create slow query
        QueryAnalysis::create([
            'query_hash' => md5('SELECT * FROM users'),
            'query' => 'SELECT * FROM users',
            'execution_count' => 10,
            'total_time' => 1500,
            'avg_time' => 150,
            'last_executed_at' => now(),
        ]);

        $recommendations = $this->analyzer->getRecommendations();

        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);
        $this->assertArrayHasKey('type', $recommendations[0]);
        $this->assertArrayHasKey('suggested_ttl', $recommendations[0]);
    }

    /** @test */
    public function it_can_get_top_queries(): void
    {
        QueryAnalysis::create([
            'query_hash' => md5('SELECT * FROM posts'),
            'query' => 'SELECT * FROM posts',
            'execution_count' => 50,
            'total_time' => 1000,
            'avg_time' => 20,
            'last_executed_at' => now(),
        ]);

        $topQueries = $this->analyzer->getTopQueries(10);

        $this->assertIsArray($topQueries);
        $this->assertNotEmpty($topQueries);
        $this->assertEquals(50, $topQueries[0]['executions']);
    }
}
