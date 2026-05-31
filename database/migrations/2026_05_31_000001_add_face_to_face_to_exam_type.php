<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exams')) {
            return;
        }

        // Expand enum to include face_to_face alongside existing values
        DB::statement("ALTER TABLE exams MODIFY COLUMN exam_type ENUM('scheduled','online','face_to_face') NOT NULL DEFAULT 'online'");

        // Migrate existing 'scheduled' rows to 'face_to_face'
        DB::statement("UPDATE exams SET exam_type = 'face_to_face' WHERE exam_type = 'scheduled'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('exams')) {
            return;
        }

        DB::statement("UPDATE exams SET exam_type = 'scheduled' WHERE exam_type = 'face_to_face'");
        DB::statement("ALTER TABLE exams MODIFY COLUMN exam_type ENUM('scheduled','online') NOT NULL DEFAULT 'online'");
    }
};
