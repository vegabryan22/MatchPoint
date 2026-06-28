<?php

namespace App\Http\Requests\Notifications;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['email_enabled' => ['required', 'boolean'], 'database_enabled' => ['required', 'boolean'], 'match_reminders' => ['required', 'boolean'], 'results' => ['required', 'boolean'], 'champions' => ['required', 'boolean']];
    }
}
