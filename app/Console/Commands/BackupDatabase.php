<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:database';

    /**
     * The console command description.
     */
    protected $description = 'Backup the MySQL database to storage/app/backups';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');

            $backupPath = storage_path('app/backups');
            
            // Create backups directory if it doesn't exist
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            // Generate backup filename with timestamp
            $filename = $database . '_backup_' . date('Y_m_d_His') . '.sql';
            $backupFile = $backupPath . '/' . $filename;

            // Build mysqldump command
            $command = sprintf(
                'mysqldump -h %s -u %s -p%s %s > %s',
                escapeshellarg($host),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($backupFile)
            );

            // Execute the command
            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                $this->info("Database backup created successfully: {$filename}");

                // Keep only last 10 backups to save space
                $this->cleanupOldBackups($backupPath, 10);

                return Command::SUCCESS;
            } else {
                $this->error("Failed to create database backup. Return code: {$returnCode}");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("Error creating backup: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Clean up old backups, keeping only the specified number.
     */
    private function cleanupOldBackups(string $backupPath, int $keepCount): void
    {
        $files = glob($backupPath . '/*.sql');
        
        if (count($files) > $keepCount) {
            // Sort files by modification time (oldest first)
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            // Delete oldest files
            $deleteCount = count($files) - $keepCount;
            for ($i = 0; $i < $deleteCount; $i++) {
                if (file_exists($files[$i])) {
                    unlink($files[$i]);
                    $this->info("Deleted old backup: " . basename($files[$i]));
                }
            }
        }
    }
}
