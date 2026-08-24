<?php

namespace App\Enums;

/**
 * As redes onde o produto publica — ou pretende publicar.
 *
 * A chave é canônica (vai pro banco e pras rotas); o nome exibido vem de
 * `rotulos.php` — nomes de marca mudam (Twitter virou X) e não podem arrastar
 * migration junto.
 *
 * Estão aqui as redes mapeadas no doc 10. Ficaram **de fora de propósito**:
 * Reddit (o plano gratuito proíbe uso comercial) e Slack (é chat interno, não
 * rede de publicação).
 */
enum Plataforma: string
{
    /*
     * ⚠️ A ORDEM AQUI É A ORDEM DA TELA.
     *
     * `cases()` é o que a tela de Conexões e o laudo percorrem, então esta lista
     * está em ordem de **prioridade do produto** — não por situação, que é
     * assunto de `situacao()`. Duplicar a situação aqui, em comentário, só criaria
     * duas verdades para discordarem depois.
     */

    // ── As quatro que importam, nesta ordem ─────────────────────────────────
    case Youtube = 'youtube';
    case Tiktok = 'tiktok';
    case Facebook = 'facebook';
    case Instagram = 'instagram';

    // ── Publica hoje, sem depender de aprovação de ninguém (DEC-29) ─────────
    case Bluesky = 'bluesky';

    // ── No roteiro, sem urgência ────────────────────────────────────────────
    /** Pega carona no mesmo App Review do Instagram e do Facebook (DEC-30). */
    case Threads = 'threads';
    /** Barreira zero também: a permissão é self-service, liberada em minutos. */
    case Linkedin = 'linkedin';

    // ── Mapeadas, sem decisão ───────────────────────────────────────────────
    case Pinterest = 'pinterest';
    /** ⚠️ Única que cobra POR PUBLICAÇÃO — decisão de custo, não técnica. */
    case X = 'x';
    case Mastodon = 'mastodon';
    case Discord = 'discord';
    case LinkedinPagina = 'linkedin_pagina';
    case Snapchat = 'snapchat';
    /** Não aceita vídeo — entraria só pelo lado das imagens. */
    case GoogleBusiness = 'google_business';

    public function rotulo(): string
    {
        return __("rotulos.plataforma.{$this->value}");
    }

    /**
     * ⭐ **Como esta rede se conecta** — e é ela quem responde, não a tela.
     *
     * ⛔ A tela decidia isto com `if (rede === 'youtube')`, e **tudo que não era
     * YouTube caía no formulário do Bluesky**. Com uma rede só de cada tipo isso
     * passava; ao ligar a Meta, o modal do Facebook começou a pedir senha de
     * aplicativo do Bluesky.
     *
     * Só existem duas formas, e a diferença é onde a pessoa digita a senha:
     *   - `autorizacao` — ela sai daqui, autoriza no site da rede e volta. A
     *     senha dela **nunca passa por nós**.
     *   - `senha_de_aplicativo` — a rede não tem esse fluxo, e entrega uma senha
     *     secundária que a pessoa gera e revoga quando quiser.
     */
    public function formaDeConexao(): string
    {
        return match ($this) {
            self::Bluesky => 'senha_de_aplicativo',
            /*
             * ⭐ A terceira forma, e ela existe por causa da federacao: antes de
             * autorizar, a pessoa precisa dizer ONDE a conta dela mora — nao
             * existe "o Mastodon" para onde mandar todo mundo (DEC-138).
             */
            self::Mastodon => 'servidor_e_autorizacao',
            /*
             * ⭐ A quarta forma: a pessoa cria o webhook no proprio Discord e
             * cola o endereco. Nao ha autorizacao nem senha — o ENDERECO e a
             * credencial (DEC-141).
             */
            self::Discord => 'endereco_de_webhook',
            default => 'autorizacao',
        };
    }

    /**
     * A ressalva sobre o número de seguidores desta rede — ou `null`.
     *
     * ⚠️ Existe por causa do YouTube, que devolve o número **arredondado para
     * baixo com 3 algarismos**. Sem a frase, quem tem 1.234 inscritos vê 1.230
     * aqui, 1.234 no YouTube Studio, e conclui que o nosso número está errado.
     */
    public function notaDosSeguidores(): ?string
    {
        return $this->nota('seguidores');
    }

    /**
     * O que esta rede NÃO conta sobre um post — ou `null`.
     *
     * ⭐ É a frase que substitui o zero (DEC-94/95). O Bluesky não tem
     * visualização no protocolo: mostrar `0` ali seria afirmar que ninguém viu,
     * quando o certo é que ninguém conta.
     */
    public function notaDoPost(): ?string
    {
        return $this->nota('post');
    }

    /**
     * ⛔ **O que esta rede NÃO deixa conferir depois de publicar** — ou `null`.
     *
     * ⚠️ A promessa do produto é a DEC-31: o painel só diz que subiu depois de
     * **reler** o post na rede. O LinkedIn não deixa — reler exige
     * `r_member_social`, que é restrita a aprovados (DEC-106).
     *
     * ⭐ Então o painel diz **qual** é o grau de certeza, em vez de fingir que é
     * o mesmo das outras. Mentir sobre isso é exatamente o defeito que o
     * produto existe para não ter.
     *
     * Rede sem frase aqui é rede onde a conferência acontece de verdade.
     */
    public function notaDaProva(): ?string
    {
        return $this->nota('prova');
    }

    /**
     * A coluna de `destinos` pela qual os posts DESTA rede se comparam.
     *
     * ⭐ É o que permite um gráfico por rede em vez de uma tabela comparativa
     * (DEC-94). O YouTube ordena por visualização; o Bluesky **não tem**
     * visualização, então ordena por curtida. Forçar as duas na mesma medida
     * somaria coisas que não são a mesma coisa.
     *
     * ⛔ `null` = esta rede não tem número comparável ainda, e por isso ela não
     * ganha gráfico. Silêncio não promete.
     */
    public function metricaDeComparacao(): ?string
    {
        return match ($this) {
            self::Youtube => 'visualizacoes',
            self::Bluesky => 'curtidas',
            default => null,
        };
    }

    private function nota(string $qual): ?string
    {
        $chave = "rotulos.nota_de_metrica.{$qual}.{$this->value}";
        $texto = __($chave);

        // Rede sem frase cadastrada não mostra nada: silêncio não promete.
        return $texto === $chave ? null : $texto;
    }

    /**
     * Em que pé a rede está.
     *
     * `match` sem `default` de propósito: rede nova obriga a decidir a situação
     * na hora, em vez de aparecer na tela como algo que ninguém decidiu.
     */
    public function situacao(): SituacaoDaRede
    {
        return match ($this) {
            self::Bluesky => SituacaoDaRede::Disponivel,

            self::Linkedin,
            self::Youtube,
            self::Instagram,
            self::Facebook,
            self::Threads,
            self::Tiktok,
            self::X,
            self::Pinterest,
            self::Mastodon,
            self::Discord => SituacaoDaRede::Planejada,

            /*
             * ⛔ Decididas como FORA, com motivo escrito (doc 28): o Snapchat
             * nao tem API de publicacao organica — nao e fila, e ausencia de
             * endpoint — e o Google Meu Negocio publica ficha de
             * estabelecimento, nao vitrine de video vertical.
             */
            self::Snapchat,
            self::GoogleBusiness => SituacaoDaRede::Fora,

            default => SituacaoDaRede::EmEstudo,
        };
    }

    /**
     * A rede tem regras de mídia registradas (doc 07 §6 / doc 10)?
     *
     * Só entram no laudo as que têm. Julgar um arquivo contra limites que
     * ninguém pesquisou seria inventar — e o laudo perde o sentido.
     */
    public function temEspecificacao(): bool
    {
        /*
         * ⚠️ Nem rede em estudo nem rede descartada tem regra pesquisada —
         * julgar um arquivo contra limites que ninguem levantou seria inventar,
         * e o laudo perde exatamente o que o torna util.
         */
        return ! in_array($this->situacao(), [SituacaoDaRede::EmEstudo, SituacaoDaRede::Fora], true);
    }

    /** @return list<self> */
    public static function comEspecificacao(): array
    {
        return array_values(array_filter(self::cases(), fn (self $p) => $p->temEspecificacao()));
    }

    public static function paraSelecao(): array
    {
        return array_map(
            fn (self $p) => ['valor' => $p->value, 'rotulo' => $p->rotulo()],
            self::cases()
        );
    }
}
