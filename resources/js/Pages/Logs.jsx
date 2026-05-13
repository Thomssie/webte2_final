import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import '../../css/CasConsole.css';
import AppLayout from '../Layouts/AppLayout';
import translations from '../translations';

// Vykresluje stranku na stiahnutie CAS logov do CSV.
// Pouziva sa cez Inertia route /logs.
export default function Logs() {
    const [language, setLanguage] = useState(
        localStorage.getItem('app_language') || 'en'
    );
    const [error, setError] = useState('');

    const t = translations[language].logsPage;

    useEffect(() => {
        // Aktualizuje jazyk stranky po kliknuti na jazykovy prepinac v layout-e.
        // Pouziva sa ako listener udalosti language-change v tomto useEffect-e.
        function handleLanguageChange(event) {
            setLanguage(event.detail);
        }

        window.addEventListener('language-change', handleLanguageChange);

        return () => {
            window.removeEventListener('language-change', handleLanguageChange);
        };
    }, []);

    // Stiahne CSV export vsetkych logov.
    // Pouziva sa po kliknuti na tlacidlo stiahnutia CSV.
    async function downloadLogsCsv() {
        setError('');

        try {
            const response = await fetch('/web/cas/logs/export', {
                method: 'GET',
            });

            if (!response.ok) {
                setError(t.csvExportFailed);
                return;
            }

            const blob = await response.blob();
            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = downloadUrl;
            link.download = 'cas_logs.csv';
            document.body.appendChild(link);
            link.click();

            link.remove();
            window.URL.revokeObjectURL(downloadUrl);
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : t.csvExportFailed);
        }
    }

    return (
        <AppLayout>
            <Head title={t.pageTitle} />

            <section className="cas-console">
                <div>
                    <h1 className="cas-title">{t.pageTitle}</h1>
                    <p>{t.intro}</p>
                </div>

                <div className="cas-actions">
                    <button type="button" className="cas-button" onClick={downloadLogsCsv}>
                        {t.downloadCsv}
                    </button>
                </div>

                {error && (
                    <p className="simulation-error">{error}</p>
                )}
            </section>
        </AppLayout>
    );
}
