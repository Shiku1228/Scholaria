<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:cleanup {--execute : Actually delete rows (otherwise dry-run)} {--include-jobs : Include cleanup for the jobs table (high risk)}', function () {
    $execute = (bool) $this->option('execute');
    $includeJobs = (bool) $this->option('include-jobs');

    $sessionsDays = (int) env('DB_CLEANUP_SESSIONS_DAYS', 7);
    $failedJobsDays = (int) env('DB_CLEANUP_FAILED_JOBS_DAYS', 30);
    $lockGraceMinutes = (int) env('DB_CLEANUP_LOCK_GRACE_MINUTES', 10);
    $jobBatchesDays = (int) env('DB_CLEANUP_JOB_BATCHES_DAYS', 30);
    $jobsDays = (int) env('DB_CLEANUP_JOBS_DAYS', 7);

    $now = now();

    $targets = [];

    $sessionsCutoff = $now->copy()->subDays($sessionsDays)->timestamp;
    $targets[] = [
        'table' => 'sessions',
        'criteria' => "last_activity < now - {$sessionsDays}d",
        'query' => fn () => DB::table('sessions')->where('last_activity', '<', $sessionsCutoff),
    ];

    $cacheCutoff = $now->timestamp;
    $targets[] = [
        'table' => 'cache',
        'criteria' => 'expiration < now',
        'query' => fn () => DB::table('cache')->where('expiration', '<', $cacheCutoff),
    ];

    $lockCutoff = $now->copy()->subMinutes($lockGraceMinutes)->timestamp;
    $targets[] = [
        'table' => 'cache_locks',
        'criteria' => "expiration < now - {$lockGraceMinutes}m",
        'query' => fn () => DB::table('cache_locks')->where('expiration', '<', $lockCutoff),
    ];

    $passwordResetCutoff = $now->copy()->subHours(24);
    $targets[] = [
        'table' => 'password_reset_tokens',
        'criteria' => 'created_at < now - 24h',
        'query' => fn () => DB::table('password_reset_tokens')
            ->whereNotNull('created_at')
            ->where('created_at', '<', $passwordResetCutoff),
    ];

    $failedJobsCutoff = $now->copy()->subDays($failedJobsDays);
    $targets[] = [
        'table' => 'failed_jobs',
        'criteria' => "failed_at < now - {$failedJobsDays}d",
        'query' => fn () => DB::table('failed_jobs')->where('failed_at', '<', $failedJobsCutoff),
    ];

    $jobBatchesCutoff = $now->copy()->subDays($jobBatchesDays)->timestamp;
    $targets[] = [
        'table' => 'job_batches',
        'criteria' => "(finished_at|cancelled_at) < now - {$jobBatchesDays}d",
        'query' => fn () => DB::table('job_batches')->where(function ($q) use ($jobBatchesCutoff) {
            $q->where(function ($q2) use ($jobBatchesCutoff) {
                $q2->whereNotNull('finished_at')->where('finished_at', '<', $jobBatchesCutoff);
            })->orWhere(function ($q2) use ($jobBatchesCutoff) {
                $q2->whereNotNull('cancelled_at')->where('cancelled_at', '<', $jobBatchesCutoff);
            });
        }),
    ];

    if ($includeJobs) {
        $jobsCutoff = $now->copy()->subDays($jobsDays)->timestamp;
        $targets[] = [
            'table' => 'jobs',
            'criteria' => "reserved_at is null AND available_at < now - {$jobsDays}d",
            'query' => fn () => DB::table('jobs')
                ->whereNull('reserved_at')
                ->where('available_at', '<', $jobsCutoff),
        ];
    }

    $rows = [];

    foreach ($targets as $target) {
        $table = $target['table'];
        $criteria = $target['criteria'];

        $total = DB::table($table)->count();
        $candidateQuery = ($target['query'])();
        $candidates = (clone $candidateQuery)->count();

        $deleted = 0;
        if ($execute && $candidates > 0) {
            if ($table === 'jobs') {
                $activeReserved = DB::table('jobs')->whereNotNull('reserved_at')->count();
                if ($activeReserved > 0) {
                    $rows[] = [$table, $total, $candidates, 0, $criteria, 'SKIPPED (reserved jobs exist)'];
                    Log::warning('db:cleanup skipped jobs cleanup because reserved jobs exist', [
                        'reserved_jobs' => $activeReserved,
                        'candidates' => $candidates,
                    ]);
                    continue;
                }
            }

            $deleted = (clone $candidateQuery)->delete();
        }

        $rows[] = [$table, $total, $candidates, $deleted, $criteria, $execute ? 'EXECUTE' : 'DRY-RUN'];

        Log::info('db:cleanup table processed', [
            'table' => $table,
            'mode' => $execute ? 'execute' : 'dry-run',
            'criteria' => $criteria,
            'total' => $total,
            'candidates' => $candidates,
            'deleted' => $deleted,
        ]);
    }

    $this->table(
        ['table', 'rows_total', 'candidates', 'deleted', 'criteria', 'mode'],
        $rows
    );

    if (! $includeJobs) {
        $this->line('Note: jobs cleanup is disabled by default. Re-run with --include-jobs only if you are sure it is safe.');
    }

    $this->line($execute ? 'Cleanup executed.' : 'Dry-run only. Re-run with --execute to delete rows.');
})->purpose('Safely clean up expired/old rows from framework tables (dry-run by default)');
