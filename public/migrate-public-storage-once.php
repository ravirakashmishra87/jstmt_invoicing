<?php

/**
 * One-shot GoDaddy helper: copy legacy storage/app/public uploads into public/storage.
 * Visit once after deploy, then this file deletes itself.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

$status = Artisan::call('storage:migrate-public-uploads');
echo Artisan::output();
echo $status === 0 ? "OK\n" : "FAILED\n";

@unlink(__FILE__);
