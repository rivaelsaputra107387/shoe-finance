import '../css/app.css';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { currentUnitName, setupUnitContext } from '@/Utils/unitContext';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'SIA Shoe Workshop';

createInertiaApp({
    title: (title) => `${title} - ${currentUnitName() || appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        setupUnitContext(props.initialPage);

        const root = createRoot(el);
        root.render(<App {...props} />);
    },
    progress: {
        color: '#6366f1',
        showSpinner: true,
    },
});
