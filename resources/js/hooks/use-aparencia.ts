import { useEffect, useState } from 'react';

export type Aparencia = 'claro' | 'escuro' | 'sistema';

const CHAVE = 'aparencia';

const consultaDoSistema = () => window.matchMedia('(prefers-color-scheme: dark)');

const sistemaPrefereEscuro = () => consultaDoSistema().matches;

function aplicar(aparencia: Aparencia) {
    const escuro = aparencia === 'escuro' || (aparencia === 'sistema' && sistemaPrefereEscuro());

    document.documentElement.classList.toggle('dark', escuro);
    // O tema tambem e marcado num atributo pra que o CSS possa dar a ele
    // prioridade sobre o `prefers-color-scheme` do navegador.
    document.documentElement.dataset.theme = escuro ? 'dark' : 'light';
}

function aparenciaSalva(): Aparencia {
    const salva = localStorage.getItem(CHAVE) as Aparencia | null;

    return salva === 'claro' || salva === 'escuro' || salva === 'sistema' ? salva : 'sistema';
}

function aoMudarOSistema() {
    aplicar(aparenciaSalva());
}

/** Chamado uma vez na carga, antes do React montar, pra nao piscar em branco. */
export function iniciarAparencia() {
    aplicar(aparenciaSalva());
    consultaDoSistema().addEventListener('change', aoMudarOSistema);
}

export function useAparencia() {
    const [aparencia, definirEstado] = useState<Aparencia>('sistema');

    const definirAparencia = (modo: Aparencia) => {
        definirEstado(modo);
        localStorage.setItem(CHAVE, modo);
        aplicar(modo);
    };

    useEffect(() => {
        definirAparencia(aparenciaSalva());

        return () => consultaDoSistema().removeEventListener('change', aoMudarOSistema);
    }, []);

    return { aparencia, definirAparencia };
}
