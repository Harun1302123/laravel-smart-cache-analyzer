<?php

namespace SmartCache\Analyzer\Services\Drivers;

class FileDriverAnalyzer extends CacheDriverAnalyzer
{
    /**
     * Get File cache-specific statistics.
     */
    public function getStats(): array
    {
        $stats = [
            'driver' => 'file',
            'disk_usage' => null,
            'file_count' => null,
            'total_size' => null,
        ];

        try {
            if ($this->config['track_disk_usage'] ?? true) {
                $stats['disk_usage'] = $this->trackDiskUsage();
            }
            
            if ($this->config['analyze_file_sizes'] ?? true) {
                $stats['file_analysis'] = $this->analyzeFileSizes();
            }
            
        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }

        return $stats;
    }

    /**
     * Check if File cache supports specific feature.
     */
    public function supports(string $feature): bool
    {
        return in_array($feature, [
            'disk_usage',
            'file_analysis',
            'cleanup_tracking',
        ]);
    }

    /**
     * Track disk usage.
     */
    protected function trackDiskUsage(): array
    {
        $cachePath = $this->getCachePath();
        
        if (!is_dir($cachePath)) {
            return [
                'path' => $cachePath,
                'exists' => false,
            ];
        }
        
        $totalSize = 0;
        $fileCount = 0;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cachePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $totalSize += $file->getSize();
                $fileCount++;
            }
        }
        
        return [
            'path' => $cachePath,
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'file_count' => $fileCount,
            'avg_file_size' => $fileCount > 0 ? round($totalSize / $fileCount) : 0,
            'disk_free' => disk_free_space($cachePath),
            'disk_free_human' => $this->formatBytes(disk_free_space($cachePath)),
        ];
    }

    /**
     * Analyze file sizes distribution.
     */
    protected function analyzeFileSizes(): array
    {
        $cachePath = $this->getCachePath();
        
        $distribution = [
            '0-1KB' => 0,
            '1-10KB' => 0,
            '10-100KB' => 0,
            '100KB-1MB' => 0,
            '1MB+' => 0,
        ];
        
        $largestFiles = [];
        
        if (!is_dir($cachePath)) {
            return $distribution;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cachePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size = $file->getSize();
                
                if ($size < 1024) {
                    $distribution['0-1KB']++;
                } elseif ($size < 10240) {
                    $distribution['1-10KB']++;
                } elseif ($size < 102400) {
                    $distribution['10-100KB']++;
                } elseif ($size < 1048576) {
                    $distribution['100KB-1MB']++;
                } else {
                    $distribution['1MB+']++;
                }
                
                $largestFiles[] = [
                    'path' => $file->getPathname(),
                    'size' => $size,
                    'size_human' => $this->formatBytes($size),
                ];
            }
        }
        
        // Sort and get top 10 largest files
        usort($largestFiles, function ($a, $b) {
            return $b['size'] <=> $a['size'];
        });
        
        return [
            'distribution' => $distribution,
            'largest_files' => array_slice($largestFiles, 0, 10),
        ];
    }

    /**
     * Get cache path.
     */
    protected function getCachePath(): string
    {
        if (method_exists($this->store, 'getDirectory')) {
            return $this->store->getDirectory();
        }
        
        // Fallback to Laravel's default cache path
        return storage_path('framework/cache/data');
    }

    /**
     * Format bytes to human-readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }

    /**
     * Get driver name.
     */
    public function getDriverName(): string
    {
        return 'file';
    }
}
