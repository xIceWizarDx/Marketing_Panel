import { LucideIcon } from 'lucide-react';

/**
 * Papel do usuario. A chave e a mesma do enum PHP `App\Enums\Papel` — se mudar
 * aqui sem mudar la, o TypeScript nao acusa, mas o teste-guardiao de papel sim.
 */
export type Papel = 'admin' | 'cliente';

export interface Usuario {
    ulid: string;
    nome: string;
    email: string;
    papel: Papel;
    papelRotulo: string;
    emailVerificado: boolean;
    avatar?: string;
}

export interface Autenticacao {
    usuario: Usuario | null;
}

export interface Avisos {
    sucesso?: string;
    erro?: string;
    aviso?: string;
}

/** Preenchido so enquanto um admin esta vendo a conta de um cliente. */
export interface Impersonacao {
    adminNome: string;
    usuarioNome: string;
}

/** Uma linha de conteudo com seus proprios canais. */
export interface Grupo {
    ulid: string;
    nome: string;
    /**
     * ⚠️ Sao o MOTIVO de excluir estar bloqueado, nao enfeite.
     *
     * `redes` conta so o que esta CONECTADO — o mesmo recorte da grade, senao
     * a tela diria "1 rede" num grupo que a pessoa ve vazio.
     */
    redes: number;
    publicacoes: number;
    /** As marcas do que ha dentro — reconhece o grupo antes de ler o nome. */
    plataformas: string[];
}

/** ⭐ Em qual grupo a pessoa esta, e para onde ela pode ir. */
export interface Grupos {
    atual: Grupo;
    lista: Grupo[];
}

/** Dados que chegam em TODA pagina (vem do HandleInertiaRequests). */
export interface DadosCompartilhados {
    nomeDoApp: string;
    auth: Autenticacao;
    avisos: Avisos;
    impersonacao: Impersonacao | null;
    /** `null` para visitante e para admin — quem nao publica nao tem grupo. */
    grupos: Grupos | null;
    /** Recado de uma requisicao so: abrir o catalogo de redes ao montar. */
    abrirCatalogo?: boolean;
    [key: string]: unknown;
}

/* ── Mídia ─────────────────────────────────────────────────────────────── */

export type NivelDoAchado = 'ok' | 'atencao' | 'erro';

export type Plataforma = 'youtube' | 'instagram' | 'facebook' | 'tiktok';

/** Uma linha do laudo: o que foi conferido e o que será feito a respeito. */
export interface Achado {
    nivel: NivelDoAchado;
    mensagem: string;
    providencia: string | null;
}

export interface FichaTecnica {
    formato: string | null;
    duracao_segundos: number | null;
    largura: number | null;
    altura: number | null;
    codec_video: string | null;
    codec_audio: string | null;
    fps: number | null;
    bitrate: number | null;
    tamanho_bytes: number | null;
}

export interface Laudo {
    disponivel: boolean;
    indisponivel_porque: string | null;
    ficha: FichaTecnica;
    por_rede: Record<string, Achado[]>;
}

/** Página do paginador do Laravel. */
export interface Paginado<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

export interface Migalha {
    titulo: string;
    url: string;
}

export interface ItemDeMenu {
    titulo: string;
    url: string;
    icone?: LucideIcon | null;
    /**
     * Outros caminhos que acendem este item.
     *
     * ⚠️ Existe porque nem toda tela mora na propria URL do item: o compositor
     * abre em `/publicar`, por cima da lista de publicacoes, e sem isto a
     * navegacao inteira apagava enquanto ele estava aberto.
     */
    ativoEm?: string[];
    /** Tela ainda não construída: aparece apagada, não leva a 404. */
    emBreve?: boolean;
}
