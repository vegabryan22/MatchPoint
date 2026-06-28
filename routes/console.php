<?php

use App\Jobs\SendMatchReminders;
use App\Services\AuditService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(AuditService::class)->prune())
    ->dailyAt('02:30')
    ->name('audit:prune')
    ->withoutOverlapping();

Schedule::call(function (): void {
    collect(glob(storage_path('app/tmp/reports/*')) ?: [])->filter(fn (string $path): bool => filemtime($path) < now()->subDay()->timestamp)->each(fn (string $path) => @unlink($path));
})->dailyAt('03:00')->name('reports:prune')->withoutOverlapping();
Schedule::job(new SendMatchReminders)->everyFiveMinutes()->withoutOverlapping();
