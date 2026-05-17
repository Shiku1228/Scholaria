<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CheckUserRole extends Command
{
    protected $signature = 'check:user-role';
    protected $description = 'Check role of a specific user';

    public function handle()
    {
        $email = 'superadmin@scholaria.com';
        
        $this->info("Checking user: {$email}");
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User not found");
            return 1;
        }
        
        $this->info("User found: " . $user->email);
        
        // Get role names
        $roleNames = $user->getRoleNames();
        $this->info("Roles: " . $roleNames->implode(', '));
        
        // Check specific roles
        $this->info("Has Student role: " . ($user->hasRole('Student') ? 'YES' : 'NO'));
        $this->info("Has Teacher role: " . ($user->hasRole('Teacher') ? 'YES' : 'NO'));
        $this->info("Has Admin role: " . ($user->hasRole('Admin') ? 'YES' : 'NO'));
        $this->info("Has Super Admin role: " . ($user->hasRole('Super Admin') ? 'YES' : 'NO'));
        
        return 0;
    }
}
