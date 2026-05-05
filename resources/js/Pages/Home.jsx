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

            <section className="home">
                <div className="home-intro">
                    <h1>{t.pageTitle}</h1>
                    <p>{t.intro}</p>
                </div>

                <div className="home-grid">
                    <Link href="/cas" className="home-card">
                        <h2>{t.casTitle}</h2>
                        <p>{t.casDescription}</p>
                    </Link>

                    <div className="home-card disabled">
                        <h2>{t.animationsTitle}</h2>
                        <p>{t.animationsDescription}</p>
                    </div>

                    <div className="home-card disabled">
                        <h2>{t.docsTitle}</h2>
                        <p>{t.docsDescription}</p>
                    </div>
                </div>
            </section>
        </AppLayout>
    );
}
