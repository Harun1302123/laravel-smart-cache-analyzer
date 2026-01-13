<?php

namespace SmartCache\Analyzer\Models;

use Illuminate\Database\Eloquent\Model;

class CacheMetric extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'smart_cache_metrics';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cache_key',
        'hits',
        'misses',
        'last_hit_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'hits' => 'integer',
        'misses' => 'integer',
        'last_hit_at' => 'datetime',
    ];

    /**
     * Get the hit ratio for this cache key.
     */
    public function getHitRatioAttribute(): float
    {
        $total = $this->hits + $this->misses;
        return $total > 0 ? round(($this->hits / $total) * 100, 2) : 0;
    }
}
