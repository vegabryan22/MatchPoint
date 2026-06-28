<?php

namespace Tests;

use App\Enums\ParticipantType;
use App\Enums\RoleName;
use App\Enums\TournamentStatus;
use App\Models\Role;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    protected function administrator(): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => RoleName::Administrator->value],
            ['name' => RoleName::Administrator->label()],
        );
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    protected function fakeImage(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent($name, $png);
    }

    protected function registrationTournament(ParticipantType $type = ParticipantType::Individual, array $attributes = []): Tournament
    {
        return Tournament::factory()->create([
            'participant_type' => $type,
            'status' => TournamentStatus::Registration,
            'registration_starts_at' => now()->subHour(),
            'registration_ends_at' => now()->addDay(),
            ...$attributes,
        ]);
    }
}
