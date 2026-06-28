<?php

namespace App\Services;

use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class SettingService
{
    public function __construct(private readonly SettingRepositoryInterface $settings) {}

    public function grouped(): Collection
    {
        return $this->settings->allGrouped();
    }

    public function updateMany(array $values): void
    {
        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                $setting = $this->settings->findByKey($key);
                $this->settings->update($setting, $this->normalize($setting->type, $value));
            }
        });
    }

    private function normalize(string $type, mixed $value): string
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL) ? '1' : '0',
            'integer' => (string) ((int) $value),
            'json' => json_encode($value, JSON_THROW_ON_ERROR),
            default => (string) $value,
        };
    }
}
