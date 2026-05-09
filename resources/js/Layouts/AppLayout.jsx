import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import '../../css/AppLayout.css';
import translations from '../translations';

export default function AppLayout({ children }) {
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

    return (
        <div className="page-shell">
            <header className="navbar">
                <Link href="/" className="navbar-brand">
                    {t.appName}
                </Link>

                <nav className="navbar-links">
                    <Link href="/">{t.home}</Link>
                    <Link href="/cas">{t.casConsole}</Link>
                    <Link href="/animations/ball-beam">{t.ballBeam}</Link>
                    <Link href="/animations/inverted-pendulum">{t.invertedPendulum}</Link>
                    <span>{t.logs}</span>
                    <span>{t.apiDocs}</span>
                    <span>{t.statistics}</span>
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
