<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

abstract class BaseCalendarController extends Controller
{
    protected function buildAgenda(array $rows, string $dateKey = 'date'): array
    {
        // Sort by datetime
        usort($rows, function ($a, $b) {
            $ad = $a['datetime'] ?? null;
            $bd = $b['datetime'] ?? null;
            return strcmp((string) $ad, (string) $bd);
        });

        $byDate = [];
        foreach ($rows as $r) {
            $date = (string) ($r[$dateKey] ?? '');
            if ($date === '') {
                continue;
            }
            $byDate[$date][] = $r;
        }

        ksort($byDate);
        return $byDate;
    }

    protected function safeDateTime($value): ?string
    {
        if ($value === null) {
            return null;
        }
        try {
            // value can be string/datetime
            return (string) $value;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function logIf($msg, \Throwable $e = null): void
    {
        try {
            Log::error($msg . ($e ? (': ' . $e->getMessage()) : ''));
        } catch (\Throwable) {
            // ignore
        }
    }

    protected function hasColumns(string $table, array $cols): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }
        foreach ($cols as $c) {
            if (!Schema::hasColumn($table, $c)) {
                return false;
            }
        }
        return true;
    }

    protected function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }

    protected function parseDate($dt): ?string
    {
        if ($dt === null) {
            return null;
        }
        try {
            return (string) \Carbon\Carbon::parse((string) $dt)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}

