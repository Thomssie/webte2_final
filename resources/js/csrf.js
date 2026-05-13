// Vracia Laravel CSRF token z meta tagu v hlavnej Blade sablone.
export function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}
