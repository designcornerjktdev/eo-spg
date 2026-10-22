<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Company name & logo shown in the sidebar, sign-in page and browser title.
 */
class Branding
{
    public const DISK = 'public';

    public static function companyName(): string
    {
        return Setting::get('company_name') ?: config('app.name');
    }

    public static function logoPath(): ?string
    {
        return Setting::get('logo_path');
    }

    /**
     * @return array{name: string, logo_url: string|null}
     */
    public static function toArray(): array
    {
        $path = static::logoPath();

        return [
            'name' => static::companyName(),
            // Version query busts the browser cache when the logo is replaced.
            'logo_url' => $path ? route('branding.logo', ['v' => substr(md5($path), 0, 8)], false) : null,
        ];
    }
}
