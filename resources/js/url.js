const applicationRoutes = new Set([
    'animations',
    'api',
    'api-docs',
    'cas',
    'logs',
    'openapi.json',
    'statistics',
    'web',
]);

// Vrati verejny podadresar aplikacie, ak je projekt nasadeny mimo korena domeny.
export function getBasePath() {
    const firstSegment = window.location.pathname.split('/').filter(Boolean)[0] || '';

    if (!firstSegment || applicationRoutes.has(firstSegment)) {
        return '';
    }

    return `/${firstSegment}`;
}

// Vytvori absolutnu URL cestu, ktora funguje lokalne aj v produkcnom podadresari.
export function appUrl(path = '/') {
    const normalizedPath = path.startsWith('/') ? path : `/${path}`;

    return `${getBasePath()}${normalizedPath}`;
}
