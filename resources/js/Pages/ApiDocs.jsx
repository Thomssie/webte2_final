import { Head } from '@inertiajs/react';
import SwaggerUI from 'swagger-ui-dist/swagger-ui-es-bundle.js';
import 'swagger-ui-dist/swagger-ui.css';
import { useEffect, useState } from 'react';
import '../../css/ApiDocs.css';
import AppLayout from '../Layouts/AppLayout';
import translations from '../translations';

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

        // Swagger UI nacita OpenAPI JSON v aktualnom jazyku a vykresli vsetky API metody.
        SwaggerUI({
            dom_id: '#swagger-ui',
            url: `/openapi.json?lang=${language}`,
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
                </div>

                <div id="swagger-ui" className="api-docs-viewer" />
            </section>
        </AppLayout>
    );
}
