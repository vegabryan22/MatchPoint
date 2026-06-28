<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MatchResultController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TournamentChampionController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\TournamentDrawController;
use App\Http\Controllers\TournamentGroupController;
use App\Http\Controllers\TournamentRegistrationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->middleware('throttle:30,1')->name('dashboard.data');
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');
    Route::get('/statistics/{type}/{participant}', [StatisticsController::class, 'show'])->name('statistics.show');
    Route::get('/champions', [TournamentChampionController::class, 'index'])->name('champions.index');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::put('/notifications/preferences', [NotificationController::class, 'update'])->name('notifications.preferences');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/reports/export', [ReportController::class, 'export'])->middleware('throttle:10,1')->name('reports.export');
    Route::patch('/players/{player}/status', [PlayerController::class, 'toggleStatus'])->name('players.status');
    Route::resource('players', PlayerController::class);
    Route::patch('/teams/{team}/status', [TeamController::class, 'toggleStatus'])->name('teams.status');
    Route::resource('teams', TeamController::class);
    Route::post('/tournaments/{tournament}/duplicate', [TournamentController::class, 'duplicate'])->name('tournaments.duplicate');
    Route::patch('/tournaments/{tournament}/status', [TournamentController::class, 'transition'])->name('tournaments.status');
    Route::get('/tournaments/{tournament}/registrations', [TournamentRegistrationController::class, 'index'])->name('tournaments.registrations.index');
    Route::post('/tournaments/{tournament}/registrations', [TournamentRegistrationController::class, 'store'])->name('tournaments.registrations.store');
    Route::delete('/tournaments/{tournament}/registrations/{participant}', [TournamentRegistrationController::class, 'destroy'])->name('tournaments.registrations.destroy');
    Route::post('/tournaments/{tournament}/registrations/import', [TournamentRegistrationController::class, 'import'])->name('tournaments.registrations.import');
    Route::get('/tournaments/{tournament}/registrations/export/csv', [TournamentRegistrationController::class, 'exportCsv'])->name('tournaments.registrations.export.csv');
    Route::get('/tournaments/{tournament}/registrations/export/xlsx', [TournamentRegistrationController::class, 'exportXlsx'])->name('tournaments.registrations.export.xlsx');
    Route::get('/tournaments/{tournament}/draw', [TournamentDrawController::class, 'show'])->name('tournaments.draws.show');
    Route::get('/tournaments/{tournament}/draw/create', [TournamentDrawController::class, 'create'])->name('tournaments.draws.create');
    Route::post('/tournaments/{tournament}/draw/preview', [TournamentDrawController::class, 'preview'])->name('tournaments.draws.preview');
    Route::post('/tournaments/{tournament}/draw', [TournamentDrawController::class, 'store'])->name('tournaments.draws.store');
    Route::delete('/tournaments/{tournament}/draw', [TournamentDrawController::class, 'destroy'])->name('tournaments.draws.destroy');
    Route::get('/tournaments/{tournament}/groups', [TournamentGroupController::class, 'show'])->name('tournaments.groups.show');
    Route::post('/tournaments/{tournament}/groups', [TournamentGroupController::class, 'store'])->name('tournaments.groups.store');
    Route::post('/tournaments/{tournament}/groups/qualify', [TournamentGroupController::class, 'qualify'])->name('tournaments.groups.qualify');
    Route::get('/matches/{match}/result', [MatchResultController::class, 'edit'])->name('matches.results.edit');
    Route::post('/matches/{match}/result', [MatchResultController::class, 'store'])->name('matches.results.store');
    Route::put('/matches/{match}/result', [MatchResultController::class, 'update'])->name('matches.results.update');
    Route::resource('tournaments', TournamentController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::resource('users', UserController::class);
        Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
