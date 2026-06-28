<?php

namespace Tests\Feature;

use App\Jobs\ImportTournamentRegistrations;
use App\Models\Player;
use App\Services\TournamentRegistrationImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TournamentRegistrationImportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_import_reports_successful_and_failed_rows(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament();
        Player::factory()->create(['nickname' => 'KnownPlayer', 'email' => 'known@example.com']);
        $file = UploadedFile::fake()->createWithContent(
            'players.csv',
            "nickname,email\nKnownPlayer,known@example.com\nMissing,missing@example.com\n",
        );

        $response = $this->actingAs($admin)->post(route('tournaments.registrations.import', $tournament), [
            'file' => $file,
        ]);

        $response->assertRedirect()->assertSessionHas('import_result', fn ($result) => $result['imported'] === 1 && $result['failed'] === 1);
        $this->assertSame(1, $tournament->players()->count());
    }

    public function test_large_csv_import_is_queued(): void
    {
        Queue::fake();
        Storage::fake('local');
        config(['matchpoint.registrations.queue_threshold_bytes' => 1]);
        $admin = $this->administrator();
        $tournament = $this->registrationTournament();
        $file = UploadedFile::fake()->createWithContent('players.csv', "nickname,email\nPlayer,p@example.com\n");

        $this->actingAs($admin)->post(route('tournaments.registrations.import', $tournament), ['file' => $file])
            ->assertRedirect();

        Queue::assertPushed(ImportTournamentRegistrations::class);
    }

    public function test_queued_import_processes_and_removes_temporary_file(): void
    {
        Storage::fake('local');
        $admin = $this->administrator();
        $tournament = $this->registrationTournament();
        Player::factory()->create(['nickname' => 'QueuedStar', 'email' => 'queued@example.com']);
        $path = 'registration-imports/queued.csv';
        Storage::disk('local')->put($path, "nickname,email\nQueuedStar,queued@example.com\n");
        $job = new ImportTournamentRegistrations($tournament->id, $admin->id, $path);

        $job->handle(app(TournamentRegistrationImportService::class));

        $this->assertSame(1, $tournament->players()->count());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_registered_participants_can_be_exported_as_csv_and_xlsx(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament();
        $player = Player::factory()->create(['nickname' => 'ExportStar']);
        $tournament->players()->attach($player, [
            'registered_by' => $admin->id,
            'source' => 'manual',
            'registered_at' => now(),
        ]);

        $csv = $this->actingAs($admin)->get(route('tournaments.registrations.export.csv', $tournament));
        $csv->assertOk()->assertDownload($tournament->slug.'-inscripciones.csv');
        $this->assertStringContainsString('ExportStar', $csv->streamedContent());

        $xlsx = $this->actingAs($admin)->get(route('tournaments.registrations.export.xlsx', $tournament));
        $xlsx->assertOk()->assertDownload($tournament->slug.'-inscripciones.xlsx');
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $xlsx->headers->get('content-type'));
    }
}
