<?php

namespace SmartCache\Analyzer\Models;

use Illuminate\Database\Eloquent\Model;

class QueryAnalysis extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'smart_cache_query_analyses';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'query_hash',
        'query',
        'execution_count',
        'total_time',
        'avg_time',
        'last_executed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'execution_count' => 'integer',
        'total_time' => 'decimal:2',
        'avg_time' => 'decimal:2',
        'last_executed_at' => 'datetime',
    ];

    /**
     * Scope to get slow queries.
     */
    public function scopeSlow($query, float $threshold = 100)
    {
        return $query->where('avg_time', '>', $threshold);
    }

    /**
     * Scope to get frequently executed queries.
     */
    public function scopeFrequent($query, int $threshold = 10)
    {
        return $query->where('execution_count', '>', $threshold);
    }

    /**
     * Get potential time savings if cached.
     */
    public function getPotentialSavingsAttribute(): float
    {
        return round($this->execution_count * $this->avg_time, 2);
    }
}
