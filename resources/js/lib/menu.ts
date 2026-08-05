import { Building2, Compass, FileVideo, Layers, ScrollText, Settings2, Users } from 'lucide-react';

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
    { titulo: 'Visão geral', url: '/painel', rota: 'painel', icone: Compass },
    { titulo: 'Publicações', url: '/publicacoes', rota: 'publicacoes', icone: FileVideo },
];

const menuDoAdmin: ItemDeMenu[] = [
    { titulo: 'Visão geral', url: '/admin/painel', rota: 'admin.painel', icone: Compass },
    { titulo: 'Clientes', url: '/admin/clientes', rota: 'admin.clientes.listar', icone: Users },
    { titulo: 'Impersonações', url: '/admin/impersonacoes', rota: 'admin.impersonacoes.listar', icone: ScrollText },
    { titulo: 'Plataforma', url: '/admin/plataforma', rota: 'admin.plataforma', icone: Building2, emBreve: true },
];

export function menuPara(papel: Papel): ItemDeMenu[] {
    return papel === 'admin' ? menuDoAdmin : menuDoCliente;
}

/** No celular a barra de baixo mostra só o que já funciona — espaço é escasso. */
export function menuDeToque(papel: Papel): ItemDeMenu[] {
    return menuPara(papel).filter((item) => !item.emBreve);
}

/**
 * Abas de "Minha conta".
 *
 * ⚠️ **Grupos so aparece para o cliente.** O admin nao publica, entao para ele
 * a aba seria uma tela vazia com um botao que nao leva a nada.
 */
export function menuDaConta(papel: Papel): ItemDeMenu[] {
    return [
        { titulo: 'Perfil', url: '/minha-conta/perfil', rota: 'minha-conta.perfil.editar', icone: Settings2 },
        { titulo: 'Senha', url: '/minha-conta/senha', rota: 'minha-conta.senha.editar', icone: Settings2 },
        { titulo: 'Aparência', url: '/minha-conta/aparencia', rota: 'minha-conta.aparencia', icone: Settings2 },
        ...(papel === 'cliente' ? [{ titulo: 'Grupos', url: '/minha-conta/grupos', rota: 'grupos', icone: Layers }] : []),
    ];
}
