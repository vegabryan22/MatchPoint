<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'MatchPoint', 'type' => 'string', 'group' => 'general', 'label' => 'Nombre del sitio', 'description' => 'Nombre visible de la plataforma.', 'is_public' => true],
            ['key' => 'timezone', 'value' => 'America/Costa_Rica', 'type' => 'string', 'group' => 'general', 'label' => 'Zona horaria', 'description' => 'Zona horaria usada para torneos y partidos.', 'is_public' => true],
            ['key' => 'registration_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'security', 'label' => 'Registro público', 'description' => 'Permite que visitantes creen cuentas.', 'is_public' => true],
            ['key' => 'maintenance_message', 'value' => '', 'type' => 'string', 'group' => 'general', 'label' => 'Mensaje operativo', 'description' => 'Aviso breve mostrado a los usuarios.', 'is_public' => true],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
