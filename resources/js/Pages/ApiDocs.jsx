import { Head } from '@inertiajs/react';
import SwaggerUI from 'swagger-ui-dist/swagger-ui-es-bundle.js';
import { useEffect, useState } from 'react';
import '../../css/ApiDocs.css';
import AppLayout from '../Layouts/AppLayout';
import translations from '../translations';
import { appUrl } from '../url';

export default function ApiDocs() {
    const [language, setLanguage] = useState(
        localStorage.getItem('app_language') || 'en'
    );

    const t = translations[language].apiDocsPage;

    useEffect(() => {
        function handleLanguageChange(event) {
            setLanguage(event.detail);
        }

        window.addEventListener('language-change', handleLanguageChange);

        return () => {
            window.removeEventListener('language-change', handleLanguageChange);
        };
    }, []);

    useEffect(() => {
        const swaggerElement = document.getElementById('swagger-ui');

        if (swaggerElement) {
            swaggerElement.innerHTML = '';
        }

        // Swagger UI nacita OpenAPI JSON vygenerovany Scramble z Laravel rout.
        SwaggerUI({
            dom_id: '#swagger-ui',
            url: appUrl('/openapi.json'),
            deepLinking: true,
            docExpansion: 'list',
            defaultModelsExpandDepth: 1,
            persistAuthorization: true,
        });
    }, [language]);

    return (
        <AppLayout>
            <Head title={t.pageTitle} />

            <section className="api-docs-page">
                <div className="api-docs-header">
                    <h1>{t.pageTitle}</h1>
                    <p>{t.intro}</p>
                    <a
                        className="api-docs-pdf-link"
                        href={appUrl('/api-docs/pdf')}
                        target="_blank"
                        rel="noreferrer"
                    >
                        {t.downloadPdf}
                    </a>
                </div>

                <div id="swagger-ui" className="api-docs-viewer" />
            </section>
        </AppLayout>
    );
}
