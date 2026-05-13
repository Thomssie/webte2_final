import '../css/app.css';
import '../css/ApiDocs.css';
import '../css/AppLayout.css';
import '../css/BallBeam.css';
import '../css/CasConsole.css';
import '../css/Home.css';
import '../css/InvertedPendulum.css';
import '../css/Statistics.css';
import './bootstrap';
import 'swagger-ui-dist/swagger-ui.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
