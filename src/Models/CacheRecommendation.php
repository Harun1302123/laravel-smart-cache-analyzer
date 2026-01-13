<?php

namespace SmartCache\Analyzer\Models;

use Illuminate\Database\Eloquent\Model;

class CacheRecommendation extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'smart_cache_recommendations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'query_hash',
        'query',
        'priority',
        'suggested_ttl',
        'reason',
        'potential_savings',
        'status',
        'auto_applied',
        'applied_config',
        'applied_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'suggested_ttl' => 'integer',
        'potential_savings' => 'float',
        'auto_applied' => 'boolean',
        'applied_at' => 'datetime',
        'applied_config' => 'array',
    ];

    /**
     * Scope to get pending recommendations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved recommendations.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get high priority recommendations.
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    /**
     * Scope to get recommendations by priority threshold.
     */
    public function scopePriorityThreshold($query, string $threshold)
    {
        $priorities = match($threshold) {
            'high' => ['high'],
            'medium' => ['high', 'medium'],
            'low' => ['high', 'medium', 'low'],
            default => ['high'],
        };

        return $query->whereIn('priority', $priorities);
    }

    /**
     * Mark recommendation as applied.
     */
    public function markAsApplied(array $config = []): void
    {
        $this->update([
            'status' => 'applied',
            'auto_applied' => true,
            'applied_config' => $config,
            'applied_at' => now(),
        ]);
    }

    /**
     * Approve recommendation.
     */
    public function approve(): void
    {
        $this->update(['status' => 'approved']);
    }

    /**
     * Reject recommendation.
     */
    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }
}
