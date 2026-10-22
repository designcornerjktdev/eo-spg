import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { route as routeFn } from 'ziggy-js';
import { initializeTheme } from './hooks/use-appearance';

declare global {
    const route: typeof routeFn;
}

// Browser title suffix follows the company name set by the superadmin.
let appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const setAppName = (props: Record<string, unknown>) => {
    const branding = props.branding as { name?: string; logo_url?: string | null } | undefined;
    if (branding?.name) appName = branding.name;

    // The favicon follows the company logo, falling back to the default icon.
    const favicon = document.getElementById('app-favicon') as HTMLLinkElement | null;
    const href = branding?.logo_url ?? '/favicon.ico';
    if (favicon && favicon.getAttribute('href') !== href) favicon.setAttribute('href', href);
};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        setAppName(props.initialPage.props);
        const root = createRoot(el);

        // Sync the company name on every page render, before the page's <Head> computes the title.
        root.render(
            <App {...props}>
                {({ Component, key, props: pageProps }) => {
                    setAppName(pageProps);
                    return <Component key={key} {...pageProps} />;
                }}
            </App>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
