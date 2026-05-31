<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\Quiz;
$quizzes = Quiz::select('id','course_id','title')->get()->toArray();
echo json_encode($quizzes);
?>
