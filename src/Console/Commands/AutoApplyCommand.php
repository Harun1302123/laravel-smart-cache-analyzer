<?php

namespace SmartCache\Analyzer\Console\Commands;

use Illuminate\Console\Command;
use SmartCache\Analyzer\Services\AutoApplyService;
use SmartCache\Analyzer\Models\CacheRecommendation;

class AutoApplyCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:auto-apply
                            {--sync : Sync recommendations from analysis first}
                            {--approve=* : Approve specific recommendation IDs}
                            {--reject=* : Reject specific recommendation IDs}
                            {--list : List pending recommendations}
                            {--dry-run : Show what would be applied without actually applying}';

    /**
     * The console command description.
     */
    protected $description = 'Automatically apply caching recommendations';

    /**
     * Execute the console command.
     */
    public function handle(AutoApplyService $service): int
    {
        // List pending recommendations
        if ($this->option('list')) {
            return $this->listRecommendations();
        }

        // Approve recommendations
        if ($this->option('approve')) {
            return $this->approveRecommendations($service);
        }

        // Reject recommendations
        if ($this->option('reject')) {
            return $this->rejectRecommendations($service);
        }

        // Sync recommendations from analysis
        if ($this->option('sync')) {
            $this->info('🔄 Syncing recommendations from analysis...');
            $synced = $service->syncRecommendations();
            $this->components->info("Synced {$synced} new recommendations");
            $this->newLine();
        }

        // Process and apply recommendations
        $this->info('🚀 Processing recommendations...');
        $this->newLine();

        $result = $service->processRecommendations();

        if ($result['status'] === 'disabled') {
            $this->components->warn($result['message']);
            $this->components->info('Enable auto-apply in config/smart-cache.php or set SMART_CACHE_AUTO_APPLY=true');
            return self::SUCCESS;
        }

        if ($result['processed'] === 0) {
            $this->components->info($result['message']);
            return self::SUCCESS;
        }

        // Display results
        if ($result['dry_run'] ?? false) {
            $this->components->warn('🔍 DRY RUN MODE - No changes were made');
            $this->newLine();
        }

        $this->components->info("Processed {$result['processed']} of {$result['total']} recommendations");
        $this->newLine();

        // Show details
        foreach ($result['results'] as $item) {
            if ($item['success']) {
                $this->components->twoColumnDetail(
                    '✓ ' . $item['query'],
                    'TTL: ' . $item['ttl'] . 's'
                );
            } else {
                $this->components->error('✗ Failed: ' . ($item['error'] ?? 'Unknown error'));
            }
        }

        $this->newLine();
        
        if ($result['dry_run'] ?? false) {
            $this->components->info('Remove --dry-run or set SMART_CACHE_AUTO_APPLY_DRY_RUN=false to apply changes');
        }

        return self::SUCCESS;
    }

    /**
     * List pending recommendations.
     */
    protected function listRecommendations(): int
    {
        $recommendations = CacheRecommendation::pending()
            ->orderByDesc('potential_savings')
            ->get();

        if ($recommendations->isEmpty()) {
            $this->components->info('No pending recommendations');
            return self::SUCCESS;
        }

        $this->components->info('Pending Recommendations:');
        $this->newLine();

        $headers = ['ID', 'Priority', 'Query', 'TTL', 'Savings (ms)', 'Reason'];
        $rows = [];

        foreach ($recommendations as $rec) {
            $priority = match($rec->priority) {
                'high' => '<fg=red>HIGH</>',
                'medium' => '<fg=yellow>MEDIUM</>',
                'low' => '<fg=green>LOW</>',
                default => $rec->priority,
            };

            $rows[] = [
                $rec->id,
                $priority,
                substr($rec->query, 0, 50) . '...',
                $rec->suggested_ttl . 's',
                round($rec->potential_savings, 2),
                substr($rec->reason, 0, 30),
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();

        $this->components->info('Use --approve=1,2,3 to approve specific recommendations');
        $this->components->info('Use --reject=1,2,3 to reject specific recommendations');

        return self::SUCCESS;
    }

    /**
     * Approve recommendations.
     */
    protected function approveRecommendations(AutoApplyService $service): int
    {
        $ids = $this->option('approve');
        
        if (empty($ids)) {
            $this->components->error('No recommendation IDs provided');
            return self::FAILURE;
        }

        $count = $service->approveRecommendations($ids);
        $this->components->info("Approved {$count} recommendations");

        return self::SUCCESS;
    }

    /**
     * Reject recommendations.
     */
    protected function rejectRecommendations(AutoApplyService $service): int
    {
        $ids = $this->option('reject');
        
        if (empty($ids)) {
            $this->components->error('No recommendation IDs provided');
            return self::FAILURE;
        }

        $count = $service->rejectRecommendations($ids);
        $this->components->info("Rejected {$count} recommendations");

        return self::SUCCESS;
    }
}
