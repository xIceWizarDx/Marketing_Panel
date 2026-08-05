import { Building2, Compass, FileVideo, ScrollText, Settings2, Users } from 'lucide-react';

import { type ItemDeMenu, type Papel } from '@/types';

/**
 * Menu lateral — fonte única da navegação.
 *
 * DEC-17: a navegação é por TAREFA, nunca por rede social. YouTube, Instagram,
 * TikTok e Facebook aparecem como FILTRO dentro das telas, jamais como item de
 * menu. Foi o que os três concorrentes de referência fazem, mesmo com 13-15
 * redes: um menu por rede vira 15 itens e a pessoa se perde.
 *
 * ⚠️ `emBreve: true` = a tela ainda não existe. O item aparece apagado e não
 * clicável, em vez de levar a um 404 — item de menu que quebra é pior que item
 * ausente. **Ao construir a tela, apagar a marca aqui.**
 */

/*
 * ⚠️ **Conexoes NAO esta aqui.**
 *
 * Conectar rede deixou de ser uma tela (DEC-63): o estado das redes vive na
 * Visao geral, que e a primeira coisa que a pessoa abre. Um item de menu
 * apontaria para um lugar que nao existe mais.
 */

/*
 * ⚠️ **Publicar NAO esta aqui.**
 *
 * Publicar deixou de ser tela: e uma acao que abre por cima da lista. Como
 * item de menu, ela apareceria como um lugar para onde ir — e nao e. O botao
 * de acao vive na barra lateral, visualmente separado da navegacao.
 */
const menuDoCliente: ItemDeMenu[] = [
    { titulo: 'Visão geral', url: '/painel', icone: Compass },
    // ⭐ `/publicar` acende Publicações: o compositor abre POR CIMA da lista, e
    // durante todo o gesto de publicar a navegação precisa dizer onde se está.
    { titulo: 'Publicações', url: '/publicacoes', ativoEm: ['/publicar'], icone: FileVideo },
];

const menuDoAdmin: ItemDeMenu[] = [
    { titulo: 'Visão geral', url: '/admin/painel', icone: Compass },
    { titulo: 'Clientes', url: '/admin/clientes', icone: Users },
    { titulo: 'Impersonações', url: '/admin/impersonacoes', icone: ScrollText },
    { titulo: 'Plataforma', url: '/admin/plataforma', icone: Building2, emBreve: true },
];

/**
 * ⭐ Este item está aceso? — **a regra mora aqui, e só aqui**.
 *
 * ⚠️ Antes cada barra tinha a sua cópia, e as duas comparavam a URL INTEIRA.
 * Bastava um filtro (`/publicacoes?aba=falharam`) para a navegação apagar — e a
 * pessoa perdia a única referência de onde estava, justamente ao recarregar.
 *
 * O que vem depois de `?` ou `#` não diz em que tela se está: é recorte da
 * mesma tela. Por isso a comparação é só do caminho.
 */
export function itemAtivo(item: ItemDeMenu, urlAtual: string): boolean {
    const caminho = urlAtual.split(/[?#]/)[0];

    return [item.url, ...(item.ativoEm ?? [])].some((base) => caminho === base || caminho.startsWith(`${base}/`));
}

export function menuPara(papel: Papel): ItemDeMenu[] {
    return papel === 'admin' ? menuDoAdmin : menuDoCliente;
}

/** No celular a barra de baixo mostra só o que já funciona — espaço é escasso. */
export function menuDeToque(papel: Papel): ItemDeMenu[] {
    return menuPara(papel).filter((item) => !item.emBreve);
}

export const menuDaConta: ItemDeMenu[] = [
    { titulo: 'Perfil', url: '/minha-conta/perfil', icone: Settings2 },
    { titulo: 'Senha', url: '/minha-conta/senha', icone: Settings2 },
    { titulo: 'Aparência', url: '/minha-conta/aparencia', icone: Settings2 },
];
