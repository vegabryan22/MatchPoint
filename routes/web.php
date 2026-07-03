<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GameClubController;
use App\Http\Controllers\MatchResultController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicFormQrController;
use App\Http\Controllers\QuickRegistrationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TournamentChampionController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\TournamentDrawController;
use App\Http\Controllers\TournamentGroupController;
use App\Http\Controllers\TournamentRegistrationController;
use App\Http\Controllers\TournamentRulesController;
use App\Http\Controllers\TournamentScheduleController;
use App\Http\Controllers\TournamentStaffController;
use App\Http\Controllers\TournamentStationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/inscripcion/{tournament}', [QuickRegistrationController::class, 'create'])
    ->middleware('throttle:60,1')->name('quick-registrations.create');
Route::post('/inscripcion/{tournament}', [QuickRegistrationController::class, 'store'])
    ->middleware('throttle:10,1')->name('quick-registrations.store');
Route::get('/inscripcion/{tournament}/confirmacion/{reference}', [QuickRegistrationController::class, 'confirmation'])
    ->middleware('throttle:60,1')->name('quick-registrations.confirmation');

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
    Route::post('/game-clubs/import/popular', [GameClubController::class, 'importPopular'])->middleware('throttle:2,1')->name('game-clubs.import-popular');
    Route::resource('game-clubs', GameClubController::class)->except(['show']);
    Route::post('/tournaments/{tournament}/duplicate', [TournamentController::class, 'duplicate'])->name('tournaments.duplicate');
    Route::get('/tournaments/{tournament}/public-forms/{form}/qr', [PublicFormQrController::class, 'image'])->name('public-forms.qr');
    Route::get('/tournaments/{tournament}/public-forms/{form}/poster', [PublicFormQrController::class, 'poster'])->name('public-forms.poster');
    Route::patch('/tournaments/{tournament}/status', [TournamentController::class, 'transition'])->name('tournaments.status');
    Route::get('/tournaments/{tournament}/registrations', [TournamentRegistrationController::class, 'index'])->name('tournaments.registrations.index');
    Route::post('/tournaments/{tournament}/registrations', [TournamentRegistrationController::class, 'store'])->name('tournaments.registrations.store');
    Route::delete('/tournaments/{tournament}/registrations/{participant}', [TournamentRegistrationController::class, 'destroy'])->name('tournaments.registrations.destroy');
    Route::patch('/tournaments/{tournament}/registrations/{participant}/game-club', [TournamentRegistrationController::class, 'assignGameClub'])->name('tournaments.registrations.game-club');
    Route::post('/tournaments/{tournament}/registrations/import', [TournamentRegistrationController::class, 'import'])->name('tournaments.registrations.import');
    Route::get('/tournaments/{tournament}/registrations/export/csv', [TournamentRegistrationController::class, 'exportCsv'])->name('tournaments.registrations.export.csv');
    Route::get('/tournaments/{tournament}/registrations/export/xlsx', [TournamentRegistrationController::class, 'exportXlsx'])->name('tournaments.registrations.export.xlsx');
    Route::get('/tournaments/{tournament}/rules/print', TournamentRulesController::class)->name('tournaments.rules.print');
    Route::get('/tournaments/{tournament}/schedule', [TournamentScheduleController::class, 'index'])->name('tournaments.schedule.index');
    Route::put('/tournaments/{tournament}/schedule/configuration', [TournamentScheduleController::class, 'configure'])->name('tournaments.schedule.configure');
    Route::post('/tournaments/{tournament}/schedule/generate', [TournamentScheduleController::class, 'generate'])->name('tournaments.schedule.generate');
    Route::delete('/tournaments/{tournament}/schedule', [TournamentScheduleController::class, 'clear'])->name('tournaments.schedule.clear');
    Route::post('/tournaments/{tournament}/stations', [TournamentStationController::class, 'store'])->name('tournaments.stations.store');
    Route::put('/tournaments/{tournament}/stations/{station}', [TournamentStationController::class, 'update'])->name('tournaments.stations.update');
    Route::delete('/tournaments/{tournament}/stations/{station}', [TournamentStationController::class, 'destroy'])->name('tournaments.stations.destroy');
    Route::get('/tournaments/{tournament}/draw', [TournamentDrawController::class, 'show'])->name('tournaments.draws.show');
    Route::get('/tournaments/{tournament}/staff', [TournamentStaffController::class, 'index'])->name('tournaments.staff.index');
    Route::post('/tournaments/{tournament}/staff/organizers', [TournamentStaffController::class, 'storeOrganizer'])->name('tournaments.staff.organizers.store');
    Route::delete('/tournaments/{tournament}/staff/organizers/{organizer}', [TournamentStaffController::class, 'destroyOrganizer'])->name('tournaments.staff.organizers.destroy');
    Route::post('/tournaments/{tournament}/staff/officials', [TournamentStaffController::class, 'storeOfficial'])->name('tournaments.staff.officials.store');
    Route::delete('/tournaments/{tournament}/staff/officials/{official}', [TournamentStaffController::class, 'destroyOfficial'])->name('tournaments.staff.officials.destroy');
    Route::get('/tournaments/{tournament}/draw/live', [TournamentDrawController::class, 'live'])->middleware('throttle:30,1')->name('tournaments.draws.live');
    Route::get('/tournaments/{tournament}/draw/create', [TournamentDrawController::class, 'create'])->name('tournaments.draws.create');
    Route::post('/tournaments/{tournament}/draw/preview', [TournamentDrawController::class, 'preview'])->name('tournaments.draws.preview');
    Route::post('/tournaments/{tournament}/draw', [TournamentDrawController::class, 'store'])->name('tournaments.draws.store');
    Route::delete('/tournaments/{tournament}/draw', [TournamentDrawController::class, 'destroy'])->name('tournaments.draws.destroy');
    Route::get('/tournaments/{tournament}/groups', [TournamentGroupController::class, 'show'])->name('tournaments.groups.show');
    Route::post('/tournaments/{tournament}/groups', [TournamentGroupController::class, 'store'])->name('tournaments.groups.store');
    Route::post('/tournaments/{tournament}/groups/qualify', [TournamentGroupController::class, 'qualify'])->name('tournaments.groups.qualify');
    Route::get('/matches/{match}/result', [MatchResultController::class, 'edit'])->name('matches.results.edit');
    Route::post('/matches/{match}/quick-result', [MatchResultController::class, 'quickStore'])->name('matches.results.quick-store');
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
