import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import '../../css/AppLayout.css';
import translations from '../translations';

export default function AppLayout({ children }) {
    const { url } = usePage();

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
        return url === path || url.startsWith(`${path}/`);
    }

    return (
        <div className="page-shell">
            <header className="navbar">
                <Link href="/" className="navbar-brand">
                    <img
                        src="/images/labLogo2.png"
                        alt="TomTib Lab logo"
                        className="navbar-logo"
                    />
                    <span>{t.appName}</span>
                </Link>


                <nav className="navbar-links">
                    <Link href="/" className={url === '/' ? 'is-active' : ''}>
                        {t.home}
                    </Link>

                    <Link href="/cas" className={isActive('/cas') ? 'is-active' : ''}>
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
                            <Link href="/animations/ball-beam">
                                {t.ballBeam}
                            </Link>

                            <Link href="/animations/inverted-pendulum">
                                {t.invertedPendulum}
                            </Link>
                        </div>
                    </div>

                    <Link href="/logs" className={isActive('/logs') ? 'is-active' : ''}>
                        {t.logs}
                    </Link>

                    <Link href="/api-docs" className={isActive('/api-docs') ? 'is-active' : ''}>
                        {t.apiDocs}
                    </Link>

                    <Link href="/statistics" className={isActive('/statistics') ? 'is-active' : ''}>
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
        </div>
    );
}
