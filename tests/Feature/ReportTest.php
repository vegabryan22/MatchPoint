<?php

namespace Tests\Feature;

use App\Enums\ReportFormat;
use App\Enums\ReportType;
use App\Models\Tournament;
use App\Models\User;
use App\Services\Reports\ReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_open_center_and_export_all_formats(): void
    {
        $admin = $this->administrator();
        $tournament = Tournament::factory()->create(['name' => 'Copa Reporte']);
        $this->actingAs($admin)->get(route('reports.index'))->assertOk()->assertSee('Centro de reportes');

        foreach (ReportFormat::cases() as $format) {
            $response = $this->actingAs($admin)->post(route('reports.export'), [
                'type' => ReportType::Summary->value,
                'format' => $format->value,
                'tournament_id' => $tournament->id,
                'participant_type' => 'individual',
            ]);
            $response->assertOk()->assertDownload();
        }
    }

    public function test_exporters_create_valid_file_signatures(): void
    {
        $tournament = Tournament::factory()->create();
        $service = app(ReportExportService::class);

        $pdf = $service->export(ReportType::Summary, ReportFormat::Pdf, ['tournament_id' => $tournament->id]);
        $xlsx = $service->export(ReportType::Summary, ReportFormat::Xlsx, ['tournament_id' => $tournament->id]);
        $csv = $service->export(ReportType::Summary, ReportFormat::Csv, ['tournament_id' => $tournament->id]);

        $this->assertSame('%PDF', file_get_contents($pdf['path'], false, null, 0, 4));
        $this->assertSame('PK', file_get_contents($xlsx['path'], false, null, 0, 2));
        $this->assertSame("\xEF\xBB\xBF", file_get_contents($csv['path'], false, null, 0, 3));
        @unlink($pdf['path']);
        @unlink($xlsx['path']);
        @unlink($csv['path']);
    }

    public function test_regular_user_cannot_export_and_tournament_is_required(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($user)->post(route('reports.export'), [
            'type' => 'summary', 'format' => 'pdf',
        ])->assertForbidden();

        $admin = $this->administrator();
        $this->actingAs($admin)->post(route('reports.export'), [
            'type' => 'summary', 'format' => 'pdf',
        ])->assertSessionHasErrors('tournament_id');
    }
}
