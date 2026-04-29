<?php

namespace App\Console\Commands;

use App\Jobs\SessionCleanupJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SecurityCleanup extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'security:cleanup {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Run security cleanup tasks including session and log cleanup';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->option('force')) {
            if ($this->confirm('This will clean up expired sessions and old logs. Continue?')) {
                $this->info('Starting security cleanup...');
            } else {
                $this->info('Security cleanup cancelled.');
                return Command::SUCCESS;
            }
        }

        try {
            $this->info('Dispatching session cleanup job...');
            
            SessionCleanupJob::dispatch();
            
            $this->info('Security cleanup job dispatched successfully!');
            $this->info('Check your logs for cleanup results.');
            
            Log::info('Security cleanup command executed', [
                'user' => 'console',
                'force' => $this->option('force'),
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Security cleanup failed: ' . $e->getMessage());
            
            Log::error('Security cleanup command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }
}
