<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PerformanceMonitorService
{
    /**
     * Measure execution time of a closure.
     *
     * @param string $operationName
     * @param Closure $operation
     * @return mixed
     */
    public function measure(string $operationName, \Closure $operation)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        try {
            $result = $operation();

            $duration = microtime(true) - $startTime;
            $memoryUsed = memory_get_usage() - $startMemory;

            $this->logPerformance($operationName, $duration, $memoryUsed, true);

            return $result;
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            $memoryUsed = memory_get_usage() - $startMemory;

            $this->logPerformance($operationName, $duration, $memoryUsed, false);

            throw $e;
        }
    }

    /**
     * Log performance metrics.
     *
     * @param string $operation
     * @param float $duration
     * @param int $memoryUsed
     * @param bool $success
     */
    protected function logPerformance(string $operation, float $duration, int $memoryUsed, bool $success): void
    {
        $metrics = [
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'memory_bytes' => $memoryUsed,
            'success' => $success,
            'timestamp' => now()->toDateTimeString(),
        ];

        // Log slow operations (>200ms)
        if ($duration > 0.2) {
            Log::warning('Slow operation detected', $metrics);
        } else {
            Log::debug('Operation performance', $metrics);
        }

        // Store metrics in cache for analysis
        $this->storeMetrics($metrics);
    }

    /**
     * Store metrics in cache.
     *
     * @param array $metrics
     */
    protected function storeMetrics(array $metrics): void
    {
        $key = 'performance_metrics:' . date('Y-m-d');
        $allMetrics = Cache::get($key, []);
        
        $allMetrics[] = $metrics;
        
        // Keep only last 1000 metrics
        if (count($allMetrics) > 1000) {
            $allMetrics = array_slice($allMetrics, -1000);
        }

        Cache::put($key, $allMetrics, now()->addDays(7));
    }

    /**
     * Get performance summary for the day.
     *
     * @return array
     */
    public function getDailySummary(): array
    {
        $key = 'performance_metrics:' . date('Y-m-d');
        $metrics = Cache::get($key, []);

        if (empty($metrics)) {
            return [
                'date' => date('Y-m-d'),
                'total_operations' => 0,
                'avg_duration_ms' => 0,
                'max_duration_ms' => 0,
                'slow_operations_count' => 0,
                'success_rate' => 100,
            ];
        }

        $totalOperations = count($metrics);
        $durations = array_column($metrics, 'duration_ms');
        $successCount = count(array_filter($metrics, fn($m) => $m['success']));
        $slowOperations = count(array_filter($metrics, fn($m) => $m['duration_ms'] > 200));

        return [
            'date' => date('Y-m-d'),
            'total_operations' => $totalOperations,
            'avg_duration_ms' => round(array_sum($durations) / $totalOperations, 2),
            'max_duration_ms' => round(max($durations), 2),
            'min_duration_ms' => round(min($durations), 2),
            'slow_operations_count' => $slowOperations,
            'success_rate' => round(($successCount / $totalOperations) * 100, 2),
        ];
    }

    /**
     * Benchmark security operations.
     *
     * @return array
     */
    public function benchmarkSecurityOperations(): array
    {
        $results = [];

        // Benchmark session validation
        $results['session_validation'] = $this->measure('session_validation', function () {
            // Simulate session validation
            return Cache::get('session_test', 'default');
        });

        // Benchmark activity logging
        $results['activity_logging'] = $this->measure('activity_logging', function () {
            // Simulate activity logging
            return Cache::put('activity_test', ['data' => 'test'], 60);
        });

        // Benchmark transaction creation
        $results['transaction_creation'] = $this->measure('transaction_creation', function () {
            // Simulate transaction creation
            return DB::table('transactions')->count();
        });

        // Benchmark encryption
        $results['encryption'] = $this->measure('encryption', function () {
            return encrypt('test_data');
        });

        // Benchmark decryption
        $results['decryption'] = $this->measure('decryption', function () {
            $encrypted = encrypt('test_data');
            return decrypt($encrypted);
        });

        return $results;
    }

    /**
     * Get performance baseline.
     *
     * @return array
     */
    public function getBaseline(): array
    {
        return Cache::remember('performance_baseline', now()->addDays(30), function () {
            return [
                'session_validation_ms' => 10,
                'activity_logging_ms' => 50,
                'transaction_creation_ms' => 100,
                'encryption_ms' => 5,
                'decryption_ms' => 5,
                'data_integrity_check_ms' => 500,
            ];
        });
    }

    /**
     * Compare current performance against baseline.
     *
     * @return array
     */
    public function compareWithBaseline(): array
    {
        $baseline = $this->getBaseline();
        $current = $this->benchmarkSecurityOperations();

        $comparison = [];

        foreach ($current as $operation => $metric) {
            $operationName = str_replace('_', ' ', $operation);
            $baselineKey = $operation . '_ms';
            
            if (isset($baseline[$baselineKey])) {
                $baselineValue = $baseline[$baselineKey];
                $currentValue = $metric['duration_ms'] ?? 0;
                
                $performancePercent = $baselineValue > 0 
                    ? round(($baselineValue / $currentValue) * 100, 2)
                    : 100;

                $comparison[$operation] = [
                    'baseline_ms' => $baselineValue,
                    'current_ms' => $currentValue,
                    'performance_percent' => $performancePercent,
                    'status' => $performancePercent >= 95 ? 'good' : 'degraded',
                ];
            }
        }

        return $comparison;
    }

    /**
     * Check if security features impact performance within acceptable limits.
     *
     * @return array
     */
    public function checkPerformanceImpact(): array
    {
        $comparison = $this->compareWithBaseline();
        $allGood = true;
        $issues = [];

        foreach ($comparison as $operation => $data) {
            if ($data['status'] === 'degraded') {
                $allGood = false;
                $issues[] = "{$operation} performance degraded: {$data['performance_percent']}% of baseline";
            }
        }

        return [
            'overall_status' => $allGood ? 'passed' : 'failed',
            'performance_threshold' => '95%',
            'all_operations_passed' => $allGood,
            'issues' => $issues,
            'comparison' => $comparison,
        ];
    }

    /**
     * Optimize database queries for security operations.
     *
     * @return array
     */
    public function optimizeSecurityQueries(): array
    {
        $optimizations = [];

        // Add indexes if they don't exist
        $indexes = [
            'transactions' => ['transaction_id', 'type', 'status', 'table_name', 'record_id', 'user_id'],
            'activity_logs' => ['user_id', 'action', 'created_at'],
            'security_audits' => ['event_type', 'severity', 'is_resolved', 'created_at'],
            'user_sessions' => ['user_id', 'token', 'expires_at'],
        ];

        foreach ($indexes as $table => $columns) {
            foreach ($columns as $column) {
                try {
                    // Check if index exists
                    $indexExists = DB::select("
                        SELECT COUNT(*) as count 
                        FROM information_schema.statistics 
                        WHERE table_schema = DATABASE() 
                        AND table_name = ? 
                        AND column_name = ?
                    ", [$table, $column]);

                    if (empty($indexExists) || $indexExists[0]->count == 0) {
                        // Create index
                        DB::statement("ALTER TABLE {$table} ADD INDEX idx_{$column} ({$column})");
                        $optimizations[] = "Created index on {$table}.{$column}";
                    }
                } catch (\Exception $e) {
                    // Index might already exist with different name
                    Log::debug("Index check for {$table}.{$column}: " . $e->getMessage());
                }
            }
        }

        return [
            'optimizations_applied' => count($optimizations),
            'details' => $optimizations,
        ];
    }

    /**
     * Clear old performance metrics.
     *
     * @param int $daysToKeep
     * @return int
     */
    public function clearOldMetrics(int $daysToKeep = 30): int
    {
        $count = 0;
        
        for ($i = 1; $i <= $daysToKeep; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $key = "performance_metrics:{$date}";
            
            if (Cache::forget($key)) {
                $count++;
            }
        }

        Log::info("Cleared old performance metrics", ['count' => $count]);

        return $count;
    }

    /**
     * Get performance report.
     *
     * @return array
     */
    public function getPerformanceReport(): array
    {
        return [
            'timestamp' => now()->toDateTimeString(),
            'daily_summary' => $this->getDailySummary(),
            'baseline_comparison' => $this->compareWithBaseline(),
            'performance_impact' => $this->checkPerformanceImpact(),
            'recommendations' => $this->getOptimizationRecommendations(),
        ];
    }

    /**
     * Get optimization recommendations.
     *
     * @return array
     */
    protected function getOptimizationRecommendations(): array
    {
        $recommendations = [];
        $comparison = $this->compareWithBaseline();

        foreach ($comparison as $operation => $data) {
            if ($data['status'] === 'degraded') {
                $recommendations[] = [
                    'operation' => $operation,
                    'issue' => 'Performance below 95% of baseline',
                    'current_ms' => $data['current_ms'],
                    'baseline_ms' => $data['baseline_ms'],
                    'recommendation' => 'Consider caching or query optimization',
                ];
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'message' => 'All security operations performing within acceptable limits',
            ];
        }

        return $recommendations;
    }
}
