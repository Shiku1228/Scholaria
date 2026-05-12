<?php

namespace App\Console\Commands;

use App\Services\CourseChatGroupService;
use Illuminate\Console\Command;

class SyncCourseChatGroups extends Command
{
    protected $signature = 'scholaria:sync-course-chat-groups';

    protected $description = 'Create missing course chat groups and synchronize memberships from courses/enrollments.';

    public function handle(CourseChatGroupService $service): int
    {
        $this->info('Synchronizing course chat groups...');

        $count = $service->syncAllCourses();

        $this->info('Done. Synced courses: ' . $count);

        return self::SUCCESS;
    }
}

