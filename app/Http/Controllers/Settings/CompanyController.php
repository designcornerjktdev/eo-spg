<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Audit\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Branding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CompanyController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/company', [
            'company' => Branding::toArray(),
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->validate([
            'company_name' => ['required', 'string', 'max:100'],
            // SVG is excluded: it can carry scripts and would be served from our own origin.
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ]);

        $old = Branding::toArray();
        Setting::put('company_name', trim($request->string('company_name')->value()));

        $currentLogo = Branding::logoPath();

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('branding', Branding::DISK);
            Setting::put('logo_path', $path);
            $this->deleteLogoFile($currentLogo);
        } elseif ($request->boolean('remove_logo')) {
            Setting::put('logo_path', null);
            $this->deleteLogoFile($currentLogo);
        }

        $audit->record($request->user(), 'settings.company_updated', $request->user(), $old, Branding::toArray());

        return back()->with('success', 'Company settings saved.');
    }

    /**
     * Served through a route instead of storage:link so it also works where symlinks are unavailable.
     */
    public function logo(): BinaryFileResponse
    {
        $path = Branding::logoPath();
        $disk = Storage::disk(Branding::DISK);
        abort_unless($path && $disk->exists($path), 404);

        // Stray buffered output (common on shared hosting) would corrupt the image bytes.
        while (! app()->runningUnitTests() && ob_get_level() > 0) {
            ob_end_clean();
        }

        return response()->file($disk->path($path), [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function deleteLogoFile(?string $path): void
    {
        if ($path) {
            Storage::disk(Branding::DISK)->delete($path);
        }
    }
}
