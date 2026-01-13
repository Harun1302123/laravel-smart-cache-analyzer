<?php

namespace SmartCache\Analyzer\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SmartCache\Analyzer\Services\CacheAnalyzer;

class AnalyzeQueryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $signature;
    protected float $time;
    protected string $normalizedSql;

    /**
     * Create a new job instance.
     */
    public function __construct(string $signature, float $time, string $normalizedSql)
    {
        $this->signature = $signature;
        $this->time = $time;
        $this->normalizedSql = $normalizedSql;
    }

    /**
     * Execute the job.
     */
    public function handle(CacheAnalyzer $analyzer): void
    {
        $analyzer->analyzeQuery($this->signature, $this->time, $this->normalizedSql);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['smart-cache', 'query-analysis'];
    }
}
