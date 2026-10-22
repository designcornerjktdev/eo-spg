<?php

namespace Tests\Feature\Settings;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Support\Branding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake(Branding::DISK);
    }

    public function test_superadmin_can_rename_company_and_upload_logo(): void
    {
        $eo = User::factory()->superadmin()->create();

        $this->actingAs($eo)->get('/settings/company')->assertOk();
        $this->actingAs($eo)->post('/settings/company', [
            'company_name' => 'Acme Events',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ])->assertSessionHasNoErrors();

        $this->assertSame('Acme Events', Branding::companyName());
        $path = Branding::logoPath();
        Storage::disk(Branding::DISK)->assertExists($path);
        $this->assertTrue(AuditLog::query()->where('action', 'settings.company_updated')->exists());

        // Branding is shared with every page, including the sign-in page for guests.
        $this->post('/logout');
        $this->get('/login')->assertInertia(fn ($page) => $page
            ->where('branding.name', 'Acme Events')
            ->where('branding.logo_url', fn ($url) => str_starts_with($url, '/branding/logo')));
        $this->get('/branding/logo')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        // The favicon and initial page title follow the branding too.
        $this->get('/login')
            ->assertSee('<link rel="icon" id="app-favicon" href="/branding/logo?v=', false)
            ->assertSee('<title inertia>Acme Events</title>', false);
    }

    public function test_default_favicon_is_used_without_a_logo(): void
    {
        $this->get('/login')->assertSee('<link rel="icon" id="app-favicon" href="/favicon.ico">', false);
    }

    public function test_replacing_and_removing_logo_cleans_up_old_files(): void
    {
        $eo = User::factory()->superadmin()->create();

        $this->actingAs($eo)->post('/settings/company', ['company_name' => 'A', 'logo' => UploadedFile::fake()->image('one.png')]);
        $first = Branding::logoPath();

        $this->actingAs($eo)->post('/settings/company', ['company_name' => 'A', 'logo' => UploadedFile::fake()->image('two.png')]);
        $second = Branding::logoPath();
        Storage::disk(Branding::DISK)->assertMissing($first);
        Storage::disk(Branding::DISK)->assertExists($second);

        $this->actingAs($eo)->post('/settings/company', ['company_name' => 'A', 'remove_logo' => true]);
        $this->assertNull(Branding::logoPath());
        Storage::disk(Branding::DISK)->assertMissing($second);
        $this->get('/branding/logo')->assertNotFound();
    }

    public function test_logo_must_be_a_raster_image_under_2mb(): void
    {
        $eo = User::factory()->superadmin()->create();

        $this->actingAs($eo)->post('/settings/company', [
            'company_name' => 'A',
            'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ])->assertSessionHasErrors('logo');

        $this->actingAs($eo)->post('/settings/company', [
            'company_name' => 'A',
            'logo' => UploadedFile::fake()->image('big.png')->size(3000),
        ])->assertSessionHasErrors('logo');

        $this->actingAs($eo)->post('/settings/company', ['company_name' => ''])->assertSessionHasErrors('company_name');
        $this->assertNull(Setting::get('company_name'));
    }

    public function test_only_superadmin_can_manage_company_settings(): void
    {
        foreach ([User::factory()->spg()->create(), User::factory()->clientUser()->create()] as $user) {
            $this->actingAs($user)->get('/settings/company')->assertForbidden();
            $this->actingAs($user)->post('/settings/company', ['company_name' => 'Hacked'])->assertForbidden();
        }

        $this->assertSame(config('app.name'), Branding::companyName());
    }
}
