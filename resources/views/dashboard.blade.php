<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Cache Analyzer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-gray-900">🚀 Smart Cache Analyzer</h1>
                <p class="mt-1 text-sm text-gray-600">Intelligent cache optimization for your Laravel application</p>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Hit Ratio</dt>
                                    <dd class="text-2xl font-semibold text-gray-900">{{ $stats['hit_ratio'] }}%</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Requests</dt>
                                    <dd class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_requests']) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Cache Keys</dt>
                                    <dd class="text-2xl font-semibold text-gray-900">{{ number_format($stats['keys_count']) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Memory Usage</dt>
                                    <dd class="text-2xl font-semibold text-gray-900">{{ $stats['memory_usage'] }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recommendations -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        💡 Caching Recommendations
                    </h3>
                    @if(count($recommendations) > 0)
                        <div class="space-y-4">
                            @foreach($recommendations as $rec)
                                <div class="border-l-4 @if($rec['priority'] === 'high') border-red-500 @else border-yellow-500 @endif p-4 bg-gray-50">
                                    <div class="flex">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    @if($rec['priority'] === 'high') bg-red-100 text-red-800 @else bg-yellow-100 text-yellow-800 @endif">
                                                    {{ strtoupper($rec['priority']) }}
                                                </span>
                                                {{ $rec['reason'] }}
                                            </p>
                                            <p class="mt-2 text-xs text-gray-600 font-mono truncate">{{ $rec['query'] }}</p>
                                            <p class="mt-1 text-xs text-gray-500">
                                                Suggested TTL: {{ $rec['suggested_ttl'] }}s | 
                                                Potential savings: {{ round($rec['potential_savings'], 2) }}ms
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">No recommendations at this time. Your cache is performing well! 🎉</p>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        🛠️ Quick Actions
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button onclick="runAnalysis()" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            🔍 Run Analysis
                        </button>
                        <button onclick="cleanupCache()" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                            🧹 Cleanup Unused Keys
                        </button>
                        <button onclick="warmCache()" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                            🔥 Warm Cache
                        </button>
                    </div>
                    <p class="mt-4 text-xs text-gray-500">
                        💡 Tip: Run these commands via CLI for better control: 
                        <code class="bg-gray-100 px-2 py-1 rounded">php artisan cache:analyze</code>
                    </p>
                </div>
            </div>
        </main>
    </div>

    <script>
        function runAnalysis() {
            alert('Analysis command: php artisan cache:analyze\n\nRun this command in your terminal for detailed analysis.');
        }

        function cleanupCache() {
            alert('Cleanup command: php artisan cache:cleanup\n\nRun this command in your terminal to remove unused cache keys.');
        }

        function warmCache() {
            alert('Warm cache command: php artisan cache:warm\n\nRun this command in your terminal to pre-populate cache.');
        }
    </script>
</body>
</html>
