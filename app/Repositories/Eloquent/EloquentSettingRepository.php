<?php

namespace App\Repositories\Eloquent;

use App\Models\Setting;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class EloquentSettingRepository implements SettingRepositoryInterface
{
    public function allGrouped(): Collection
    {
        return Setting::query()->orderBy('group')->orderBy('label')->get();
    }

    public function findByKey(string $key): Setting
    {
        return Setting::query()->where('key', $key)->firstOrFail();
    }

    public function update(Setting $setting, mixed $value): Setting
    {
        $setting->update(['value' => $value]);

        return $setting->refresh();
    }
}
