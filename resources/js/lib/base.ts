// Base path when the app is served from a subfolder (e.g. /eo-spg). Empty locally.
export const BASE = (import.meta.env.VITE_APP_BASE ?? '').replace(/\/+$/, '');

const isExternal = (url: string) => /^([a-z][a-z\d+.-]*:|\/\/|#)/i.test(url);

const hasBase = (url: string) => url === BASE || url.startsWith(BASE + '/') || url.startsWith(BASE + '?') || url.startsWith(BASE + '#');

/** Prefix an app-relative path ('/dashboard') with the base path. External URLs, anchors and already-prefixed paths are left untouched. */
export function withBase(url: string): string {
    if (!BASE || !url.startsWith('/') || isExternal(url) || hasBase(url)) return url;
    return BASE + url;
}

/** Remove the base path so the URL can be compared with app-relative paths. */
export function stripBase(url: string): string {
    if (!BASE || !hasBase(url)) return url;
    return url.slice(BASE.length) || '/';
}
