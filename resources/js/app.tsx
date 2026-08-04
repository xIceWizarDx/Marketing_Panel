import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { route as funcaoDeRota } from 'ziggy-js';

import { iniciarAparencia } from './hooks/use-aparencia';

declare global {
    const route: typeof funcaoDeRota;
}

/*
 * O nome do produto vive em UM lugar so: `APP_NAME` no .env (o `VITE_APP_NAME`
 * apenas o repassa). Nada aqui escreve o nome — renomear o produto e trocar uma
 * linha do .env, nao caçar string pelo projeto.
 */
const nomeDoApp = import.meta.env.VITE_APP_NAME ?? '';

createInertiaApp({
    title: (titulo) => [titulo, nomeDoApp].filter(Boolean).join(' · '),
    resolve: (nome) => resolvePageComponent(`./pages/${nome}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#4f46e5',
    },
});

// Aplica o tema antes do React montar, pra tela nao piscar em branco.
iniciarAparencia();
