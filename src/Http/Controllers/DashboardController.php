<?php

namespace SmartCache\Analyzer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use SmartCache\Analyzer\Services\CacheAnalyzer;

class DashboardController extends Controller
{
    protected CacheAnalyzer $analyzer;

    public function __construct(CacheAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    /**
     * Display the dashboard.
     */
    public function index(): View
    {
        return view('smart-cache::dashboard', [
            'stats' => $this->analyzer->getStats(),
            'recommendations' => $this->analyzer->getRecommendations(),
        ]);
    }

    /**
     * Get cache statistics as JSON.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->analyzer->getStats(),
        ]);
    }

    /**
     * Get caching recommendations as JSON.
     */
    public function recommendations(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->analyzer->getRecommendations(),
        ]);
    }

    /**
     * Get top queries as JSON.
     */
    public function queries(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->analyzer->getTopQueries(20),
        ]);
    }
}
