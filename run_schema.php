<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $sql = file_get_contents(__DIR__.'/schema.sql');
    DB::unprepared($sql);
    echo "Successfully executed schema.sql on remote database!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
