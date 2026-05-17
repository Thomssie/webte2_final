import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import '../../css/AppLayout.css';
import translations from '../translations';
import { appUrl, getBasePath } from '../url';

export default function AppLayout({ children }) {
    const { url } = usePage();
    const basePath = getBasePath();
    const currentUrl = basePath && url.startsWith(basePath)
        ? url.slice(basePath.length) || '/'
        : url;

    const [language, setLanguage] = useState(
        localStorage.getItem('app_language') || 'en'
    );

    const t = translations[language].navigation;

    useEffect(() => {
        document.documentElement.lang = language;
    }, [language]);

    function changeLanguage(nextLanguage) {
        localStorage.setItem('app_language', nextLanguage);
        setLanguage(nextLanguage);
        window.dispatchEvent(new CustomEvent('language-change', {
            detail: nextLanguage,
        }));
    }

    function isActive(path) {
        return currentUrl === path || currentUrl.startsWith(`${path}/`);
    }

    return (
        <div className="page-shell">
            <header className="navbar">
                <Link href={appUrl('/')} className="navbar-brand">
                    <img
                        src={appUrl('/images/labLogo2.png')}
                        alt="TomTib Lab logo"
                        className="navbar-logo"
                    />
                    <span>{t.appName}</span>
                </Link>


                <nav className="navbar-links">
                    <Link href={appUrl('/')} className={currentUrl === '/' ? 'is-active' : ''}>
                        {t.home}
                    </Link>

                    <Link href={appUrl('/cas')} className={isActive('/cas') ? 'is-active' : ''}>
                        {t.casConsole}
                    </Link>

                    <div className={`navbar-dropdown ${isActive('/animations') ? 'is-active' : ''}`}>
                        <button type="button" className="navbar-dropdown-trigger">
                            {t.simulations}
                            <span className="material-symbols-rounded navbar-dropdown-chevron">
                                keyboard_arrow_down
                            </span>
                        </button>

                        <div className="navbar-dropdown-menu">
                            <Link href={appUrl('/animations/ball-beam')}>
                                {t.ballBeam}
                            </Link>

                            <Link href={appUrl('/animations/inverted-pendulum')}>
                                {t.invertedPendulum}
                            </Link>
                        </div>
                    </div>

                    <Link href={appUrl('/logs')} className={isActive('/logs') ? 'is-active' : ''}>
                        {t.logs}
                    </Link>

                    <Link href={appUrl('/api-docs')} className={isActive('/api-docs') ? 'is-active' : ''}>
                        {t.apiDocs}
                    </Link>

                    <Link href={appUrl('/statistics')} className={isActive('/statistics') ? 'is-active' : ''}>
                        {t.statistics}
                    </Link>
                </nav>

                <div className="language-switch">
                    <button
                        type="button"
                        onClick={() => changeLanguage('sk')}
                        className={language === 'sk' ? 'is-active' : ''}
                    >
                        SK
                    </button>

                    <button
                        type="button"
                        onClick={() => changeLanguage('en')}
                        className={language === 'en' ? 'is-active' : ''}
                    >
                        EN
                    </button>
                </div>
            </header>

            <main className="page-content">
                {children}
            </main>

            <footer className="app-footer">
                ©2026 TomTibLab | All Rights Reserved
            </footer>
        </div>
    );
}
