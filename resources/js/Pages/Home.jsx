import { Head, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import '../../css/Home.css';
import AppLayout from '../Layouts/AppLayout';
import translations from '../translations';

export default function Home() {
    const [language, setLanguage] = useState(
        localStorage.getItem('app_language') || 'en'
    );

    const t = translations[language].home;

    useEffect(() => {
        function handleLanguageChange(event) {
            setLanguage(event.detail);
        }

        window.addEventListener('language-change', handleLanguageChange);

        return () => {
            window.removeEventListener('language-change', handleLanguageChange);
        };
    }, []);

    return (
        <AppLayout>
            <Head title={t.pageTitle} />

            <section className="home-hero">
                <div className="home-hero-content">
                    <h1>{t.pageTitle}</h1>
                    <p>{t.intro}</p>

                    <div className="home-actions">
                        <Link href="/cas" className="home-action home-action-primary">
                            {t.openCasConsole}
                        </Link>

                        <Link href="/animations/ball-beam" className="home-action">
                            {t.ballBeamTitle}
                        </Link>

                        <Link href="/animations/inverted-pendulum" className="home-action">
                            {t.invertedPendulumTitle}
                        </Link>
                    </div>
                </div>
            </section>
        </AppLayout>
    );
}
