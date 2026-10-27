<!DOCTYPE html>
{{-- Public consumer forms always use the light theme, whatever the device or a signed-in user prefers. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if (request()->routeIs('public-form.*')) data-force-theme="light" @endif>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php($branding = \App\Support\Branding::toArray())
        <title inertia>{{ $branding['name'] }}</title>
        {{-- Favicon follows the company logo; app.tsx keeps it in sync after the logo changes. --}}
        <link rel="icon" id="app-favicon" href="{{ $branding['logo_url'] ?? request()->getBaseUrl().'/favicon.ico' }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
