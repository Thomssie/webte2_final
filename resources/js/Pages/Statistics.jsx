import { Head } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import '../../css/Statistics.css';
import AppLayout from '../Layouts/AppLayout';
import translations from '../translations';
import { appUrl } from '../url';

const animationLabels = {
    ball_beam: {
        en: 'Ball and Beam',
        sk: 'Gulička na tyči',
    },
    inverted_pendulum: {
        en: 'Inverted Pendulum',
        sk: 'Inverzné kyvadlo',
    },
};

export default function Statistics() {
    const [language, setLanguage] = useState(
        localStorage.getItem('app_language') || 'en'
    );
    const [summary, setSummary] = useState([]);
    const [details, setDetails] = useState({});
    const [expandedItems, setExpandedItems] = useState([]);
    const [loading, setLoading] = useState(false);
    const [detailsLoading, setDetailsLoading] = useState('');
    const [error, setError] = useState('');

    const t = translations[language].statisticsPage;

    const normalizedSummary = useMemo(() => {
        const counts = {
            ball_beam: 0,
            inverted_pendulum: 0,
        };

        summary.forEach((item) => {
            counts[item.animation_type] = item.count;
        });

        return [
            {
                animationType: 'ball_beam',
                count: counts.ball_beam,
            },
            {
                animationType: 'inverted_pendulum',
                count: counts.inverted_pendulum,
            },
        ];
    }, [summary]);

    useEffect(() => {
        loadSummary();

        function handleLanguageChange(event) {
            setLanguage(event.detail);
        }

        window.addEventListener('language-change', handleLanguageChange);

        return () => {
            window.removeEventListener('language-change', handleLanguageChange);
        };
    }, []);

    async function loadSummary() {
        setLoading(true);
        setError('');

        try {
            const response = await fetch(appUrl('/web/statistics/animations'), {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                setError(data.message || t.loadFailed);
                return;
            }

            setSummary(data.summary || []);
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : t.loadFailed);
        } finally {
            setLoading(false);
        }
    }

    async function toggleDetails(animationType) {
        if (expandedItems.includes(animationType)) {
            setExpandedItems((current) => current.filter((item) => item !== animationType));
            return;
        }

        setExpandedItems((current) => [...current, animationType]);

        if (details[animationType]) {
            return;
        }

        setDetailsLoading(animationType);
        setError('');

        try {
            const response = await fetch(appUrl(`/web/statistics/animations/${animationType}`), {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                setError(data.message || t.loadFailed);
                return;
            }

            setDetails((current) => ({
                ...current,
                [animationType]: data.usages || [],
            }));
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : t.loadFailed);
        } finally {
            setDetailsLoading('');
        }
    }

    function formatDate(value) {
        if (!value) {
            return '-';
        }

        return new Intl.DateTimeFormat(language === 'sk' ? 'sk-SK' : 'en-US', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(value));
    }

    function formatLocation(usage) {
        if (usage.city && usage.country) {
            return `${usage.city}, ${usage.country}`;
        }

        if (usage.city) {
            return usage.city;
        }

        if (usage.country) {
            return usage.country;
        }

        return t.unknownLocation;
    }

    return (
        <AppLayout>
            <Head title={t.pageTitle} />

            <section className="statistics-page">
                <div className="statistics-header">
                    <h1>{t.pageTitle}</h1>
                    {/*<p>{t.intro}</p>*/}
                </div>

                <div className="statistics-toolbar">
                    <button type="button" onClick={loadSummary} disabled={loading}>
                        {loading ? t.loading : t.refresh}
                    </button>
                </div>

                {error && (
                    <p className="statistics-error">{error}</p>
                )}

                <div className="statistics-grid">
                    {normalizedSummary.map((item) => {
                        const isExpanded = expandedItems.includes(item.animationType);
                        const usageDetails = details[item.animationType] || [];
                        const label = animationLabels[item.animationType]?.[language] || item.animationType;

                        return (
                            <section key={item.animationType} className="statistics-card">
                                <div className="statistics-card-header">
                                    <div>
                                        <h2>{label}</h2>
                                        <div className="statistics-count">{item.count}</div>
                                        <div className="statistics-count-label">{t.uses}</div>
                                    </div>

                                    <button
                                        type="button"
                                        onClick={() => toggleDetails(item.animationType)}
                                    >
                                        {detailsLoading === item.animationType
                                            ? t.loading
                                            : isExpanded
                                                ? t.hideDetails
                                                : t.details}
                                    </button>
                                </div>

                                {isExpanded && (
                                    <div className="statistics-details">
                                        {usageDetails.length === 0 ? (
                                            <p className="statistics-empty">{t.noRecords}</p>
                                        ) : (
                                            <ol className="statistics-detail-list">
                                                {usageDetails.map((usage) => (
                                                    <li key={usage.id} className="statistics-detail-item">
                                                        <div>
                                                            <span className="statistics-detail-label">{t.usedAt}</span>
                                                            {formatDate(usage.created_at)}
                                                        </div>

                                                        <div>
                                                            <span className="statistics-detail-label">{t.location}</span>
                                                            {formatLocation(usage)}
                                                        </div>
                                                    </li>
                                                ))}
                                            </ol>
                                        )}
                                    </div>
                                )}
                            </section>
                        );
                    })}
                </div>
            </section>
        </AppLayout>
    );
}
