<?php

namespace App\Repositories\Contracts;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;

interface SettingRepositoryInterface
{
    /** @return Collection<int, Setting> */
    public function allGrouped(): Collection;

    public function findByKey(string $key): Setting;

    public function update(Setting $setting, mixed $value): Setting;
}
