<?php

namespace App\Http\Requests\Registrations;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageRegistrations', $this->route('tournament')) ?? false;
    }

    public function rules(): array
    {
        return [
            'attendance_status' => ['required', Rule::enum(AttendanceStatus::class)],
        ];
    }
}
