<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $descriptions = [
            RoleName::Administrator->value => 'Control total de configuración, usuarios y auditoría.',
            RoleName::Organizer->value => 'Administra torneos, inscripciones y programación.',
            RoleName::Referee->value => 'Registra y valida resultados de partidos.',
            RoleName::Player->value => 'Consulta sus torneos, partidos y estadísticas.',
            RoleName::Guest->value => 'Acceso de consulta limitado.',
        ];

        foreach (RoleName::cases() as $role) {
            Role::query()->updateOrCreate(
                ['slug' => $role->value],
                ['name' => $role->label(), 'description' => $descriptions[$role->value]],
            );
        }
    }
}
