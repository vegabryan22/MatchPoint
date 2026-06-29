<?php

namespace App\Http\Controllers;

use App\Enums\ReportFormat;
use App\Enums\ReportType;
use App\Http\Requests\Reports\ExportReportRequest;
use App\Services\AuditService;
use App\Services\Reports\ReportExportService;
use App\Services\TournamentAccessService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ReportController extends Controller
{
    public function __construct(
        private readonly ReportExportService $reports,
        private readonly AuditService $audit,
        private readonly TournamentAccessService $access,
    ) {}

    public function index(): View
    {
        Gate::authorize('exportReports');

        return view('reports.index', ['types' => ReportType::cases(), 'formats' => ReportFormat::cases(), 'tournaments' => $this->access->visibleQuery(request()->user())->latest('starts_at')->get()]);
    }

    public function export(ExportReportRequest $request): BinaryFileResponse
    {
        $type = ReportType::from($request->validated('type'));
        $format = ReportFormat::from($request->validated('format'));
        $filters = $request->validated();
        $filters['visible_tournament_ids'] = $this->access->visibleQuery($request->user())->pluck('id')->all();
        $file = $this->reports->export($type, $format, $filters);
        $this->audit->record('report.exported', null, [], ['type' => $type->value, 'format' => $format->value], $request->user()->id);

        return response()->download($file['path'], $file['filename'])->deleteFileAfterSend();
    }
}
