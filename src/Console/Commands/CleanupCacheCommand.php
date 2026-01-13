<?php

namespace SmartCache\Analyzer\Console\Commands;

use Illuminate\Console\Command;
use SmartCache\Analyzer\Services\CacheAnalyzer;

class CleanupCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:cleanup
                            {--days=7 : Number of days a key must be unused to be cleaned}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Cleanup unused cache keys';

    /**
     * Execute the console command.
     */
    public function handle(CacheAnalyzer $analyzer): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("🧹 Finding cache keys unused for {$days} days...");
        $this->newLine();

        $unusedKeys = $analyzer->getUnusedKeys($days);

        if (empty($unusedKeys)) {
            $this->components->info('No unused cache keys found!');
            return self::SUCCESS;
        }

        $this->components->warn('Found ' . count($unusedKeys) . ' unused cache keys:');
        
        foreach (array_slice($unusedKeys, 0, 10) as $key) {
            $this->components->bulletList([$key]);
        }

        if (count($unusedKeys) > 10) {
            $this->components->info('... and ' . (count($unusedKeys) - 10) . ' more');
        }

        $this->newLine();

        if ($dryRun) {
            $this->components->info('Dry run mode - no keys were deleted');
            return self::SUCCESS;
        }

        if (!$this->confirm('Do you want to delete these cache keys?', false)) {
            $this->components->info('Cleanup cancelled');
            return self::SUCCESS;
        }

        $deleted = 0;
        $progressBar = $this->output->createProgressBar(count($unusedKeys));
        $progressBar->start();

        foreach ($unusedKeys as $key) {
            try {
                \Cache::forget($key);
                $deleted++;
            } catch (\Exception $e) {
                // Continue on error
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->components->info("Successfully deleted {$deleted} cache keys");

        return self::SUCCESS;
    }
}
