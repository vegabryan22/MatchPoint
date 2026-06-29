<?php

namespace App\Http\Requests\Reports;

use App\Enums\ParticipantType;
use App\Enums\ReportFormat;
use App\Enums\ReportType;
use App\Services\TournamentAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class ExportReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('exportReports') ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ReportType::class)],
            'format' => ['required', Rule::enum(ReportFormat::class)],
            'tournament_id' => ['nullable', 'integer', 'exists:tournaments,id'],
            'participant_type' => ['nullable', Rule::enum(ParticipantType::class)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $type = ReportType::tryFrom((string) $this->input('type'));
            if ($type?->requiresTournament() && ! $this->filled('tournament_id')) {
                $validator->errors()->add('tournament_id', 'Selecciona un torneo para este reporte.');
            }
            if ($this->filled('tournament_id') && ! app(TournamentAccessService::class)->visibleQuery($this->user())->whereKey($this->integer('tournament_id'))->exists()) {
                $validator->errors()->add('tournament_id', 'No tienes acceso a ese torneo.');
            }
        }];
    }
}
