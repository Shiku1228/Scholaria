<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugAdminRoles extends Command
{
    protected $signature = 'debug:admin-roles {email}';
    protected $description = 'Debug admin user roles';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found: {$email}");
            return 1;
        }

        $this->info("=== USER DEBUG INFO ===");
        $this->info("User ID: " . $user->id);
        $this->info("User Email: " . $user->email);

        $this->info("\n=== ROLE CHECKS ===");
        $this->info("Has hasRole method: " . (method_exists($user, 'hasRole') ? 'YES' : 'NO'));

        if (method_exists($user, 'getRoleNames')) {
            $roleNames = $user->getRoleNames();
            $this->info("All roles: " . json_encode($roleNames->toArray()));
            
            $adminRoles = ['Admin', 'Super Admin', 'Content Admin', 'User Admin', 'Report Admin', 'Settings Admin'];
            foreach ($adminRoles as $adminRole) {
                $hasRole = $user->hasRole($adminRole);
                $this->info("Has role '{$adminRole}': " . ($hasRole ? 'YES' : 'NO'));
            }
        } else {
            $this->error("getRoleNames method not found");
        }

        $this->info("\n=== DATABASE CHECK ===");
        $roles = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $user->id)
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->pluck('roles.name');
            
        $this->info("Database roles: " . json_encode($roles->toArray()));

        $this->info("\n=== LEGACY ROLE CHECK ===");
        $this->info("Legacy role field: " . ($user->role ?? 'NULL'));

        return 0;
    }
}
