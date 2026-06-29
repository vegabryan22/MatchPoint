<?php

namespace Tests\Feature;

use App\Enums\PublicFormType;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFormQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_views_share_card_svg_and_printable_poster(): void
    {
        $admin = $this->administrator();
        $tournament = $this->publicTournament();

        $this->actingAs($admin)->get(route('tournaments.show', $tournament))
            ->assertOk()
            ->assertSee('QR del formulario público')
            ->assertSee('Descargar PNG')
            ->assertSee(route('quick-registrations.create', $tournament));

        $this->actingAs($admin)->get(route('public-forms.qr', [
            $tournament,
            PublicFormType::QuickRegistration,
            'format' => 'svg',
            'size' => 512,
        ]))->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertSee('<svg', false);

        $this->actingAs($admin)->get(route('public-forms.poster', [
            $tournament,
            PublicFormType::QuickRegistration,
        ]))->assertOk()
            ->assertSee('¡Escanea e inscríbete!')
            ->assertSee(route('quick-registrations.create', $tournament));
    }

    public function test_manager_downloads_high_resolution_png(): void
    {
        $response = $this->actingAs($this->administrator())->get(route('public-forms.qr', [
            $this->publicTournament(),
            PublicFormType::QuickRegistration,
            'format' => 'png',
            'size' => 1024,
            'download' => 1,
        ]));

        $response->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
    }

    public function test_non_manager_cannot_generate_publicity_qr(): void
    {
        $this->actingAs(User::factory()->create())->get(route('public-forms.qr', [
            $this->publicTournament(),
            PublicFormType::QuickRegistration,
        ]))->assertForbidden();
    }

    public function test_disabled_public_form_has_no_share_card_or_qr(): void
    {
        $tournament = Tournament::factory()->create(['quick_registration_enabled' => false]);

        $this->actingAs($this->administrator())->get(route('tournaments.show', $tournament))
            ->assertOk()
            ->assertDontSee('QR del formulario público');
        $this->actingAs($this->administrator())->get(route('public-forms.qr', [
            $tournament,
            PublicFormType::QuickRegistration,
        ]))->assertNotFound();
    }

    private function publicTournament(): Tournament
    {
        return Tournament::factory()->create([
            'quick_registration_enabled' => true,
            'quick_registration_levels' => ['7', '8'],
        ]);
    }
}
