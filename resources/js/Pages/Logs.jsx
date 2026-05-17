import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import '../../css/CasConsole.css';
import AppLayout from '../Layouts/AppLayout';
import translations from '../translations';
import { appUrl } from '../url';

// Vykresluje stranku na stiahnutie CAS logov do CSV.
// Pouziva sa cez Inertia route /logs.
export default function Logs() {
    const [language, setLanguage] = useState(
        localStorage.getItem('app_language') || 'en'
    );
    const [logs, setLogs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [sortConfig, setSortConfig] = useState({
        key: 'created_at',
        direction: 'desc',
    });

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

    useEffect(() => {
        loadLogs();
    }, []);

    // Nacita posledne CAS logy pre tabulkovy nahlad na stranke.
    // Pouziva sa pri prvom otvoreni stranky a po kliknuti na refresh.
    async function loadLogs() {
        setLoading(true);
        setError('');

        try {
            const response = await fetch(appUrl('/web/cas/logs?limit=100'), {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                setError(data.error || data.message || t.logsLoadFailed);
                return;
            }

            setLogs(data.logs || []);
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : t.logsLoadFailed);
        } finally {
            setLoading(false);
        }
    }

    // Skrati dlhy text requestu alebo odpovede, aby tabulka zostala citatelna.
    // Pouziva sa pri renderovani buniek s prikazom a vystupom.
    function truncateText(value, maxLength = 160) {
        if (!value) {
            return '-';
        }

        const normalizedValue = String(value).replace(/\s+/g, ' ').trim();

        if (normalizedValue.length <= maxLength) {
            return normalizedValue;
        }

        return `${normalizedValue.slice(0, maxLength)}...`;
    }

    // Sformatuje datum logu do aktualne nastaveneho jazyka.
    // Pouziva sa v prvom stlpci tabulky.
    function formatLogDate(value) {
        if (!value) {
            return '-';
        }

        return new Intl.DateTimeFormat(language === 'sk' ? 'sk-SK' : 'en-US', {
            dateStyle: 'medium',
            timeStyle: 'medium',
        }).format(new Date(value));
    }

    // Vrati skratenu odpoved, pri chybach uprednostni chybovu spravu.
    // Pouziva sa v stlpci odpovede.
    function getLogResponse(log) {
        return log.success ? log.output : (log.error_message || log.output);
    }

    // Prelozi technicky zdroj logu na citatelny nazov v UI.
    // Pouziva sa v stlpci Zdroj.
    function formatLogSource(source) {
        if (source === 'form') {
            return 'CAS console';
        }

        return source || '-';
    }

    // Prepina triedenie tabulky medzi vzostupnym a zostupnym smerom.
    // Pouziva sa po kliknuti na hlavicky Datum a Stav.
    function updateSort(key) {
        setSortConfig((current) => ({
            key,
            direction: current.key === key && current.direction === 'asc' ? 'desc' : 'asc',
        }));
    }

    // Vrati sipku aktualneho smeru triedenia pre hlavicku tabulky.
    // Pouziva sa v tlacidlach hlaviciek.
    function getSortIndicator(key) {
        if (sortConfig.key !== key) {
            return '';
        }

        return sortConfig.direction === 'asc' ? ' ↑' : ' ↓';
    }

    const sortedLogs = [...logs].sort((firstLog, secondLog) => {
        let firstValue;
        let secondValue;

        if (sortConfig.key === 'created_at') {
            firstValue = new Date(firstLog.created_at).getTime();
            secondValue = new Date(secondLog.created_at).getTime();
        } else if (sortConfig.key === 'success') {
            firstValue = firstLog.success ? 1 : 0;
            secondValue = secondLog.success ? 1 : 0;
        } else {
            return 0;
        }

        if (firstValue === secondValue) {
            return 0;
        }

        const sortResult = firstValue > secondValue ? 1 : -1;

        return sortConfig.direction === 'asc' ? sortResult : -sortResult;
    });

    // Stiahne CSV export vsetkych logov.
    // Pouziva sa po kliknuti na tlacidlo stiahnutia CSV.
    async function downloadLogsCsv() {
        setError('');

        try {
            const response = await fetch(appUrl('/web/cas/logs/export'), {
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

            <section className="cas-console logs-page">
                <div>
                    <h1 className="cas-title">{t.pageTitle}</h1>
                    <p>{t.intro}</p>
                </div>

                <div className="cas-actions">
                    <button type="button" className="cas-button cas-button-secondary" onClick={loadLogs} disabled={loading}>
                        {loading ? t.loading : t.refresh}
                    </button>

                    <button type="button" className="cas-button" onClick={downloadLogsCsv}>
                        {t.downloadCsv}
                    </button>
                </div>

                {error && (
                    <p className="simulation-error">{error}</p>
                )}

                <div className="logs-table-panel">
                    {loading ? (
                        <p className="cas-history-empty">{t.loading}</p>
                    ) : logs.length === 0 ? (
                        <p className="cas-history-empty">{t.noLogs}</p>
                    ) : (
                        <div className="logs-table-wrap">
                            <table className="logs-table">
                                <thead>
                                    <tr>
                                        <th>
                                            <button type="button" className="logs-sort-button" onClick={() => updateSort('created_at')}>
                                                {t.createdAt}{getSortIndicator('created_at')}
                                            </button>
                                        </th>
                                        <th>{t.source}</th>
                                        <th>{t.request}</th>
                                        <th>{t.response}</th>
                                        <th>
                                            <button type="button" className="logs-sort-button" onClick={() => updateSort('success')}>
                                                {t.status}{getSortIndicator('success')}
                                            </button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {sortedLogs.map((log) => (
                                        <tr key={log.id}>
                                            <td>{formatLogDate(log.created_at)}</td>
                                            <td>{formatLogSource(log.source)}</td>
                                            <td title={log.command || ''}>
                                                <code>{truncateText(log.command)}</code>
                                            </td>
                                            <td title={getLogResponse(log) || ''}>
                                                <code>{truncateText(getLogResponse(log))}</code>
                                            </td>
                                            <td>
                                                <span className={`logs-status ${log.success ? 'is-success' : 'is-error'}`}>
                                                    {log.success ? t.success : t.failed}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </section>
        </AppLayout>
    );
}
