<?php

namespace App\Providers;

use App\Events\MatchCompleted;
use App\Listeners\AdvanceCompletedMatch;
use App\Listeners\RecordLogout;
use App\Listeners\RecordSuccessfulLogin;
use App\Listeners\UpdateTournamentChampion;
use App\Models\GameClub;
use App\Models\Player;
use App\Models\Setting;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Observers\AuditableObserver;
use App\Policies\DashboardPolicy;
use App\Policies\ReportPolicy;
use App\Policies\StatisticsPolicy;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\GameClubRepositoryInterface;
use App\Repositories\Contracts\GameMatchRepositoryInterface;
use App\Repositories\Contracts\GroupStageRepositoryInterface;
use App\Repositories\Contracts\MatchResultRepositoryInterface;
use App\Repositories\Contracts\PlayerRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Repositories\Contracts\StatisticsRepositoryInterface;
use App\Repositories\Contracts\TeamRepositoryInterface;
use App\Repositories\Contracts\TournamentChampionRepositoryInterface;
use App\Repositories\Contracts\TournamentDrawRepositoryInterface;
use App\Repositories\Contracts\TournamentRegistrationRepositoryInterface;
use App\Repositories\Contracts\TournamentRepositoryInterface;
use App\Repositories\Contracts\TournamentScheduleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EloquentAuditLogRepository;
use App\Repositories\Eloquent\EloquentDashboardRepository;
use App\Repositories\Eloquent\EloquentGameClubRepository;
use App\Repositories\Eloquent\EloquentGameMatchRepository;
use App\Repositories\Eloquent\EloquentGroupStageRepository;
use App\Repositories\Eloquent\EloquentMatchResultRepository;
use App\Repositories\Eloquent\EloquentPlayerRepository;
use App\Repositories\Eloquent\EloquentRoleRepository;
use App\Repositories\Eloquent\EloquentSettingRepository;
use App\Repositories\Eloquent\EloquentStatisticsRepository;
use App\Repositories\Eloquent\EloquentTeamRepository;
use App\Repositories\Eloquent\EloquentTournamentChampionRepository;
use App\Repositories\Eloquent\EloquentTournamentDrawRepository;
use App\Repositories\Eloquent\EloquentTournamentRegistrationRepository;
use App\Repositories\Eloquent\EloquentTournamentRepository;
use App\Repositories\Eloquent\EloquentTournamentScheduleRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, EloquentRoleRepository::class);
        $this->app->bind(StatisticsRepositoryInterface::class, EloquentStatisticsRepository::class);
        $this->app->bind(SettingRepositoryInterface::class, EloquentSettingRepository::class);
        $this->app->bind(AuditLogRepositoryInterface::class, EloquentAuditLogRepository::class);
        $this->app->bind(DashboardRepositoryInterface::class, EloquentDashboardRepository::class);
        $this->app->bind(GameMatchRepositoryInterface::class, EloquentGameMatchRepository::class);
        $this->app->bind(GameClubRepositoryInterface::class, EloquentGameClubRepository::class);
        $this->app->bind(GroupStageRepositoryInterface::class, EloquentGroupStageRepository::class);
        $this->app->bind(MatchResultRepositoryInterface::class, EloquentMatchResultRepository::class);
        $this->app->bind(PlayerRepositoryInterface::class, EloquentPlayerRepository::class);
        $this->app->bind(TeamRepositoryInterface::class, EloquentTeamRepository::class);
        $this->app->bind(TournamentRepositoryInterface::class, EloquentTournamentRepository::class);
        $this->app->bind(TournamentRegistrationRepositoryInterface::class, EloquentTournamentRegistrationRepository::class);
        $this->app->bind(TournamentDrawRepositoryInterface::class, EloquentTournamentDrawRepository::class);
        $this->app->bind(TournamentChampionRepositoryInterface::class, EloquentTournamentChampionRepository::class);
        $this->app->bind(TournamentScheduleRepositoryInterface::class, EloquentTournamentScheduleRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Password::defaults(fn () => Password::min(10)->mixedCase()->numbers()->symbols());
        Gate::define('viewStatistics', fn (User $user): bool => app(StatisticsPolicy::class)->viewAny($user));
        Gate::define('viewDashboard', fn (User $user): bool => app(DashboardPolicy::class)->view($user));
        Gate::define('exportReports', fn (User $user): bool => app(ReportPolicy::class)->export($user));

        User::observe(AuditableObserver::class);
        Setting::observe(AuditableObserver::class);
        Player::observe(AuditableObserver::class);
        GameClub::observe(AuditableObserver::class);
        Team::observe(AuditableObserver::class);
        Tournament::observe(AuditableObserver::class);

        Event::listen(Login::class, RecordSuccessfulLogin::class);
        Event::listen(Logout::class, RecordLogout::class);
        Event::listen(MatchCompleted::class, AdvanceCompletedMatch::class);
        Event::listen(MatchCompleted::class, UpdateTournamentChampion::class);
    }
}
