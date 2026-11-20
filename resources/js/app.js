import { createInertiaApp } from '@inertiajs/react';
import { createElement } from 'react';
import { createRoot } from 'react-dom/client';

createInertiaApp({
    resolve: (name) => {
        const jsxPages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
        const tsxPages = import.meta.glob('./Pages/**/*.tsx');

        const existingPage = jsxPages[`./Pages/${name}.jsx`];

        if (existingPage) {
            return existingPage;
        }

        const reportPage = tsxPages[`./Pages/${name}.tsx`];

        return reportPage ? reportPage() : undefined;
    },
    setup({ el, App, props }) {
        createRoot(el).render(createElement(App, props));
    },
});
