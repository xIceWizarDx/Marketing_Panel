<?php

namespace App\Support\Midia;

use App\Enums\Plataforma;
use InvalidArgumentException;

/**
 * O que cada rede aceita — e o que ela faz com o que não aceita.
 *
 * Fonte: perfil canônico do doc 07 §6, verificado contra documentação oficial.
 * Limites que dependem da CONTA (ex.: teto de duração do criador no TikTok, via
 * `creator_info`) não entram aqui: são conferidos na hora de publicar, porque
 * variam por pessoa e mudam sem aviso.
 */
readonly class EspecificacaoDaRede
{
    private function __construct(
        public Plataforma $plataforma,
        public int $duracaoMinima,
        public int $duracaoMaxima,
        public int $tamanhoMaximoBytes,
        /** @var list<string> */
        public array $codecsVideo,
        /** @var list<string> */
        public array $codecsAudio,
        public bool $aceitaImagem,
        public bool $exigeVertical,
        public LimitesDeTexto $texto = new LimitesDeTexto,
        /**
         * Formatos que a rede aceita de fato.
         * `[]` = qualquer um dos `codecsVideo` serve.
         */
        public array $contêineresAceitos = [],
    ) {}

    public static function de(Plataforma $plataforma): self
    {
        return match ($plataforma) {
            // Lexicon oficial (planos-de-redes/bluesky/documentacao/lexicons/).
            // ⚠️ 100.000.000 bytes exatos, e SÓ `video/mp4` — nem `.mov`.
            Plataforma::Bluesky => new self(
                $plataforma, 1, 180, 100_000_000,
                ['h264', 'hevc'], ['aac'],
                aceitaImagem: true, exigeVertical: false,
                texto: new LimitesDeTexto(
                    // ⭐ O Bluesky conta GRAFEMAS: emoji de familia e 1, nao 7.
                    legenda: 300, medidaDaLegenda: Medida::Grafemas,
                    // ⛔ Nao tem campo de titulo: ele sobe colado no texto, e os
                    // 300 grafemas sao dos dois juntos.
                    tituloEntraNaLegenda: true,
                ),
                contêineresAceitos: ['video/mp4'],
            ),

            // Doc 10: MP4, 3s a 30min, ate 500 MB, 9:16 aceito. Publica no
            // PERFIL — a Pagina e barreira alta e entra depois.
            Plataforma::Linkedin => new self(
                $plataforma, 3, 600, 500 * 1024 * 1024,
                ['h264'], ['aac'],
                // ⛔ `false` porque o PUBLICADOR recusa imagem — e a frase do
                // laudo diz "nao publica imagem POR AQUI", que e sobre o
                // painel, nao sobre a plataforma. Declarar `true` fazia o laudo
                // dar verde numa imagem que ia falhar na hora de publicar.
                aceitaImagem: false, exigeVertical: false,
                texto: new LimitesDeTexto(legenda: 3000),
                /*
                 * ⚠️ **MP4 e so.** A documentacao oficial lista um formato
                 * unico, e mandar MOV — que o Threads e o Instagram aceitam —
                 * falha no processamento DEPOIS do envio inteiro, com o motivo
                 * generico de "nao conseguimos processar".
                 *
                 * ⚠️ Os 500 MB sao o MENOR dos dois numeros que a mesma pagina
                 * da: a secao de especificacao diz 500 MB e a descricao do campo
                 * `fileSizeBytes` diz 5 GB. Recusar no menor e seguro nas duas
                 * leituras; aceitar 5 GB pode estourar no meio do envio.
                 */
                contêineresAceitos: ['video/mp4'],
            ),

            // Os limites mais generosos das quatro da Meta (5 min / 1 GB).
            Plataforma::Threads => new self(
                $plataforma, 3, 300, 1024 * 1024 * 1024,
                ['h264', 'hevc'], ['aac'],
                aceitaImagem: true, exigeVertical: false,
                /*
                 * ⭐ **500 BYTES, não 500 caracteres** (DEC-104). A documentação
                 * é literal: *"emojis são contados como o número de bytes
                 * UTF-8"*.
                 *
                 * ⚠️ Um emoji comum ocupa 4 bytes e um com tom de pele passa de
                 * 8 — uma legenda de 480 "caracteres" com dez emojis estoura os
                 * 500 sem parecer que estourou. Contar caractere aqui deixaria
                 * a rede recusar um texto que a nossa tela disse que cabia.
                 */
                texto: new LimitesDeTexto(
                    legenda: 500, medidaDaLegenda: Medida::Bytes,
                    // ⛔ O Threads nao tem titulo: ele sobe colado na legenda,
                    // e os dois dividem os mesmos 500 bytes.
                    tituloEntraNaLegenda: true,
                ),
            ),

            // Teto de 180s no Shorts; o vídeo passa intacto (DEC-33).
            // Spec oficial: arquivo ate 256 GB. O nosso teto e o do perfil
            // canonico (07 §6), nao o da plataforma.
            Plataforma::Youtube => new self(
                $plataforma, 3, 180, 256 * 1024 * 1024,
                ['h264', 'hevc'], ['aac', 'mp3'],
                aceitaImagem: false, exigeVertical: true,
                texto: new LimitesDeTexto(
                    titulo: 100,
                    // ⚠️ A descricao e medida em BYTES: "coração" gasta 9, nao 7.
                    legenda: 5000, medidaDaLegenda: Medida::Bytes,
                    // ⚠️ Orcamento TOTAL das tags juntas, contando virgulas.
                    hashtags: 500,
                    // ⛔ O YouTube recusa video sem titulo (DEC-166). Descobrir
                    // isso DEPOIS do envio e o defeito que esta regra apaga.
                    tituloObrigatorio: true,
                ),
            ),

            // Spec oficial do Reels: 3s a 15min, 300 MB, MOV **ou** MP4.
            // O teto de 180s é nosso (perfil canônico 07 §6), não o da rede — o
            // produto é de vídeo curto, e subir 15min não é o caso de uso.
            //
            // ⭐ A Meta aceita HEVC do iPhone — Buffer e Metricool recusam.
            Plataforma::Instagram => new self(
                $plataforma, 3, 180, 300 * 1024 * 1024,
                ['h264', 'hevc'], ['aac'],
                /*
                 * ⛔ `false` porque o PUBLICADOR recusa imagem, e a frase do
                 * laudo diz "não publica imagem POR AQUI" — ela é sobre o
                 * painel, não sobre a plataforma.
                 *
                 * ⚠️ Declarar `true` fazia o laudo dar VERDE numa imagem que ia
                 * falhar na hora de publicar: a pessoa via "formato aceito" e
                 * recebia "o X recebe vídeo por aqui" depois.
                 */
                aceitaImagem: false,
                // Proporção aceita vai de 0,01:1 a 10:1 — vertical é recomendado,
                // não exigido. Recusar horizontal aqui seria regra nossa.
                exigeVertical: false,
                texto: new LimitesDeTexto(
                    legenda: 2200,
                    // ⛔ O Reels nao tem campo de titulo: ele vai colado na
                    // legenda, e os dois dividem os mesmos 2200.
                    tituloEntraNaLegenda: true,
                ),
                // ⚠️ Ao contrário do Bluesky, aceita `.mov` — vídeo de iPhone passa.
                contêineresAceitos: ['video/mp4', 'video/quicktime'],
            ),

            // ⛔ 90s é o teto do Reels no Facebook — o mais curto de TODAS as
            // redes (o Instagram aceita 15min do mesmo arquivo). Um corte de 2min
            // publica nas outras e é recusado só aqui, então a recusa tem que vir
            // ANTES do envio, dizendo qual rede não aceita.
            Plataforma::Facebook => new self(
                $plataforma, 3, 90, 300 * 1024 * 1024,
                ['h264', 'hevc'], ['aac'],
                /*
                 * ⛔ `false` porque o PUBLICADOR recusa imagem, e a frase do
                 * laudo diz "não publica imagem POR AQUI" — ela é sobre o
                 * painel, não sobre a plataforma.
                 *
                 * ⚠️ Declarar `true` fazia o laudo dar VERDE numa imagem que ia
                 * falhar na hora de publicar: a pessoa via "formato aceito" e
                 * recebia "o X recebe vídeo por aqui" depois.
                 */
                aceitaImagem: false,
                // ⚠️ Aqui a spec lista 9:16 como REQUISITO, não recomendação —
                // diferente do Instagram. Mínimo 540x1920.
                exigeVertical: true,
                texto: new LimitesDeTexto(legenda: 2200),
                contêineresAceitos: ['video/mp4', 'video/quicktime'],
            ),

            // Foto no TikTok só com domínio verificado — por isso `false` aqui.
            Plataforma::Tiktok => new self(
                $plataforma, 3, 180, 500 * 1024 * 1024,
                ['h264', 'hevc'], ['aac'],
                aceitaImagem: false, exigeVertical: true,
                /*
                 * ⚠️ 2200 runas UTF-16, diz a documentacao. Contamos por
                 * caractere, que e a medida mais proxima e a mais conservadora
                 * para o texto latino do produto.
                 */
                texto: new LimitesDeTexto(
                    legenda: 2200,
                    // ⛔ O TikTok tambem nao tem titulo separado: o `title` da
                    // API E a legenda do post.
                    tituloEntraNaLegenda: true,
                ),
                /*
                 * Os tres tipos que o envio aceita, segundo a documentacao.
                 *
                 * ⚠️ O teto de 500 MB e 180 s aqui e NOSSO (perfil canonico
                 * 07 §6), nao o da rede — ela aceita ate 4 GB, e a duracao
                 * maxima real e por CONTA, perguntada no `creator_info`
                 * (DEC-117).
                 */
                contêineresAceitos: ['video/mp4', 'video/quicktime', 'video/webm'],
            ),

            /*
             * ⚠️ **O X nao declara os limites do arquivo em lugar nenhum** da
             * documentacao consultada: nem tamanho, nem duracao, nem proporcao,
             * nem codecs, nem o limite de caracteres do texto (DEC-132).
             *
             * ⛔ Nada aqui foi inventado a partir dela. Os numeros abaixo tem
             * procedencia declarada:
             *
             * - **140 s** e o teto de video de conta comum levantado na doc 10
             *   (3 min so com Premium). Fonte de terceiro, registrada como tal.
             * - **512 MB** e o teto do PERFIL CANONICO do produto (doc 07 §6),
             *   nao da plataforma.
             * - **280 caracteres** e o limite publico e notorio do post. A
             *   pagina consultada nao o declara, e por isso ele esta aqui pelo
             *   lado seguro: sem limite nenhum, a tela deixaria escrever 3000
             *   caracteres para a rede recusar DEPOIS do video inteiro subir.
             */
            Plataforma::X => new self(
                $plataforma, 3, 140, 512 * 1024 * 1024,
                ['h264', 'hevc'], ['aac'],
                /*
                 * ⛔ `false` porque o PUBLICADOR recusa imagem, e a frase do
                 * laudo diz "não publica imagem POR AQUI" — ela é sobre o
                 * painel, não sobre a plataforma.
                 *
                 * ⚠️ Declarar `true` fazia o laudo dar VERDE numa imagem que ia
                 * falhar na hora de publicar: a pessoa via "formato aceito" e
                 * recebia "o X recebe vídeo por aqui" depois.
                 */
                aceitaImagem: false,
                exigeVertical: false,
                texto: new LimitesDeTexto(
                    legenda: 280,
                    // ⛔ O X nao tem campo de titulo: o `text` do post E a
                    // legenda, e os dois dividem os mesmos 280 caracteres.
                    tituloEntraNaLegenda: true,
                ),
                contêineresAceitos: ['video/mp4'],
            ),

            /*
             * ⭐ Encaixe de formato melhor que o de todas: o Pinterest e
             * nativamente vertical, e o 9:16 do painel serve sem reconversao.
             *
             * ⚠️ `titulo: 100` e `legenda: 800` vem da SPEC OFICIAL — sao os
             * unicos numeros desta rede que a fonte declara. Duracao, tamanho e
             * codecs sao do perfil canonico do produto (doc 07 §6), nao dela.
             */
            Plataforma::Pinterest => new self(
                $plataforma, 3, 180, 500 * 1024 * 1024,
                ['h264', 'hevc'], ['aac'],
                // ⛔ O publicador recusa imagem: o laudo tem que dizer o mesmo.
                aceitaImagem: false,
                exigeVertical: false,
                texto: new LimitesDeTexto(titulo: 100, legenda: 800),
                contêineresAceitos: ['video/mp4', 'video/quicktime'],
            ),

            /*
             * ⛔ **Aqui os limites sao de CADA SERVIDOR, nao da rede.** Um
             * Mastodon aceita video de 40 MB, o vizinho aceita 200 MB, e nenhum
             * numero e "do Mastodon".
             *
             * ⚠️ Os valores abaixo sao o perfil canonico do produto (doc 07 §6)
             * — o mais conservador que atende a maioria dos servidores. A recusa
             * de verdade vem do servidor, com o nome dele na frase.
             *
             * ⭐ 500 caracteres e o padrao do Mastodon; servidor pode aumentar,
             * quase nenhum diminui.
             */
            Plataforma::Mastodon => new self(
                $plataforma, 3, 180, 40 * 1024 * 1024,
                ['h264', 'hevc'], ['aac'],
                aceitaImagem: true, exigeVertical: false,
                texto: new LimitesDeTexto(
                    legenda: 500,
                    // ⛔ Nao tem campo de titulo: ele sobe colado no texto.
                    tituloEntraNaLegenda: true,
                ),
                contêineresAceitos: ['video/mp4', 'video/quicktime', 'video/webm'],
            ),

            /*
             * ⚠️ **O teto de arquivo aqui e do SERVIDOR, nao do Discord**: ele
             * sobe com o nivel de impulsionamento. 10 MB e o piso — o servidor
             * sem impulso — e por isso e o numero que o painel confere.
             *
             * ⛔ Conferir pelo teto mais alto deixaria passar arquivo que a
             * maioria dos servidores recusa, e a recusa (413) so chega depois do
             * envio inteiro.
             *
             * ⭐ 2000 caracteres e o limite da mensagem, declarado na
             * documentacao — e o Discord recusa o post inteiro se passar, nao
             * corta.
             */
            Plataforma::Discord => new self(
                $plataforma, 1, 600, 10 * 1024 * 1024,
                ['h264', 'hevc'], ['aac'],
                aceitaImagem: true, exigeVertical: false,
                texto: new LimitesDeTexto(
                    legenda: 2000,
                    // ⛔ Nao tem campo de titulo: ele sobe colado na mensagem.
                    tituloEntraNaLegenda: true,
                ),
                contêineresAceitos: ['video/mp4', 'video/quicktime', 'video/webm'],
            ),

            // Rede em estudo nao tem regra pesquisada. Chutar limite seria
            // inventar — e o laudo perde exatamente o que o torna util.
            default => throw new InvalidArgumentException(
                "Ainda nao ha regras de midia registradas para {$plataforma->rotulo()}."
            ),
        };
    }

    /**
     * O texto cabe nesta rede?
     *
     * ⛔ **Nunca corta — recusa.** A política do YouTube proíbe modificar o que a
     * pessoa escreveu sem consentimento explícito, e cortar em silêncio faz ela
     * descobrir só olhando o post no ar.
     *
     * @return list<Achado>
     */
    public function conferirTextos(?string $titulo, ?string $legenda, array $hashtags = []): array
    {
        $l = $this->texto;

        /*
         * ⛔ **A legenda medida é a que SOBE — hashtags incluídas, sempre.**
         *
         * ⚠️ Nenhuma rede do painel tem campo separado para hashtag: elas viajam
         * **dentro** do texto, é assim que `Destino::textoFinal()` monta e é
         * assim que o publicador manda. Medir só a legenda deixava passar o que
         * a rede ia recusar — e a recusa chega **depois** do vídeo inteiro ter
         * subido.
         *
         * ⚠️ O título entra junto **só** nas redes sem campo próprio para ele
         * (Threads, TikTok, X, Bluesky, Instagram, Mastodon, Discord); nas
         * outras ele tem orçamento separado.
         */
        $comoSobe = trim(implode(' ', array_filter([
            $l->tituloEntraNaLegenda ? $titulo : null,
            $legenda,
            ...array_map(fn (string $t) => '#'.ltrim($t, '#'), array_filter($hashtags)),
        ])));

        return array_values(array_filter([
            /*
             * ⛔ **Título obrigatório, conferido ANTES do envio** (DEC-166).
             *
             * ⚠️ Isto já foi recusa do publicador, lá na frente: o vídeo subia
             * na fila, a rede recusava, e o painel virava "não foi" em vermelho
             * — por uma coisa que dava para saber antes de clicar.
             *
             * ⭐ Contar falha é placar. Impedir é produto.
             */
            $l->tituloObrigatorio && trim((string) $titulo) === ''
                ? Achado::erro(
                    'Esta rede exige um título, e ele está vazio.',
                    'Escreva um título antes de publicar aqui.'
                )
                : null,

            // Título só é conferido sozinho quando tem campo próprio.
            $l->tituloEntraNaLegenda
                ? null
                : $l->conferir((string) $titulo, $l->titulo, $l->medidaDoTitulo, 'Título'),

            $l->conferir(
                $comoSobe,
                $l->legenda,
                $l->medidaDaLegenda,
                $l->tituloEntraNaLegenda ? 'Título, legenda e hashtags juntos' : 'Legenda e hashtags juntas'
            ),

            /*
             * ⚠️ E o orçamento PRÓPRIO das hashtags, quando a rede tem um: o
             * YouTube manda as tags num campo separado **além** de elas irem na
             * descrição. Dois limites, duas conferências.
             */
            $l->conferir($this->juntarHashtags($hashtags), $l->hashtags, $l->medidaDasHashtags, 'Hashtags'),
        ]));
    }

    /** O contêiner do arquivo serve para esta rede? */
    public function conferirContainer(string $mimeType): ?Achado
    {
        if ($this->contêineresAceitos === [] || in_array($mimeType, $this->contêineresAceitos, true)) {
            return null;
        }

        $aceitos = implode(', ', array_map(
            fn (string $mime) => strtoupper((string) str($mime)->afterLast('/')),
            $this->contêineresAceitos
        ));

        return Achado::erro(
            "{$this->plataforma->rotulo()} não aceita este formato de arquivo.",
            "Só aceita {$aceitos}. Converta o arquivo ou publique nas outras redes."
        );
    }

    /** @param list<string> $hashtags */
    private function juntarHashtags(array $hashtags): string
    {
        return implode(',', array_map(fn (string $t) => ltrim($t, '#'), array_filter($hashtags)));
    }

    /**
     * As redes que entram no laudo.
     *
     * So as que tem regra pesquisada — rede em estudo ficaria como "aceita" ou
     * "nao aceita" sem ninguem ter conferido nada.
     *
     * @return list<self>
     */
    public static function todas(): array
    {
        return array_map(self::de(...), Plataforma::comEspecificacao());
    }

    /**
     * Confere a ficha contra esta rede.
     *
     * @return list<Achado>
     */
    public function conferir(FichaTecnica $ficha, bool $ehVideo): array
    {
        return $ehVideo
            ? $this->conferirVideo($ficha)
            : $this->conferirImagem($ficha);
    }

    /** @return list<Achado> */
    private function conferirImagem(FichaTecnica $ficha): array
    {
        if (! $this->aceitaImagem) {
            return [Achado::erro(
                "{$this->plataforma->rotulo()} não publica imagem por aqui.",
                'Ela é enviada só para as outras redes escolhidas.'
            )];
        }

        $achados = [Achado::ok('Formato aceito.')];

        if ($ficha->tamanhoBytes && $ficha->tamanhoBytes > 8 * 1024 * 1024) {
            $achados[] = Achado::atencao(
                'Imagem acima de 8 MB.',
                'Vamos comprimir mantendo a resolução.'
            );
        }

        return $achados;
    }

    /** @return list<Achado> */
    private function conferirVideo(FichaTecnica $ficha): array
    {
        $achados = [];

        $achados[] = $this->conferirDuracao($ficha);
        $achados[] = $this->conferirTamanho($ficha);
        $achados[] = $this->conferirProporcao($ficha);
        $achados[] = $this->conferirVideoCodec($ficha);
        $achados[] = $this->conferirAudio($ficha);
        $achados[] = $this->conferirShorts($ficha);

        return array_values(array_filter($achados));
    }

    /**
     * O vídeo atende ao que o YouTube pede para Shorts?
     *
     * ⚠️ **A API não fala de Shorts.** Isso não está no contrato da API — é
     * comportamento do produto YouTube, documentado só na central de ajuda: até
     * **3 minutos** e **vertical**. Por isso este achado descreve o que dá para
     * verificar no arquivo, e **não promete** que o vídeo vira Short: quem
     * classifica é o YouTube, e ele não publica o critério exato.
     *
     * Prometer o que não se pode garantir seria repetir o defeito que o produto
     * combate, do outro lado.
     */
    private function conferirShorts(FichaTecnica $ficha): ?Achado
    {
        if ($this->plataforma !== Plataforma::Youtube || $ficha->duracaoSegundos === null) {
            return null;
        }

        $vertical = $ficha->ehVertical();
        $cabeNoTempo = $ficha->duracaoSegundos <= 180;

        if ($vertical && $cabeNoTempo) {
            return Achado::ok('Vertical e com menos de 3 minutos — é o que o YouTube pede para Shorts.');
        }

        return Achado::atencao(
            $vertical
                ? 'Passa de 3 minutos: o YouTube não trata como Short.'
                : 'O vídeo não é vertical: o YouTube não trata como Short.',
            'Publica normalmente, mas entra como vídeo comum — fora da aba de Shorts.'
        );
    }

    private function conferirDuracao(FichaTecnica $ficha): ?Achado
    {
        if ($ficha->duracaoSegundos === null) {
            return null;
        }

        $segundos = (int) round($ficha->duracaoSegundos);

        if ($segundos < $this->duracaoMinima) {
            return Achado::erro(
                "Vídeo de {$segundos}s — o mínimo é {$this->duracaoMinima}s.",
                'Não há como alongar o vídeo; escolha outro arquivo.'
            );
        }

        if ($segundos > $this->duracaoMaxima) {
            return Achado::erro(
                "Vídeo de {$segundos}s — o máximo é {$this->duracaoMaxima}s.",
                'Corte o vídeo ou publique só nas outras redes.'
            );
        }

        return Achado::ok("Duração de {$segundos}s, dentro do limite.");
    }

    private function conferirTamanho(FichaTecnica $ficha): ?Achado
    {
        if ($ficha->tamanhoBytes === null || $ficha->tamanhoBytes <= $this->tamanhoMaximoBytes) {
            return null;
        }

        $atual = round($ficha->tamanhoBytes / 1024 / 1024);
        $teto = round($this->tamanhoMaximoBytes / 1024 / 1024);

        return Achado::erro(
            "Arquivo de {$atual} MB — o limite é {$teto} MB.",
            'Vamos reduzir a taxa de bits mantendo a resolução.'
        );
    }

    private function conferirProporcao(FichaTecnica $ficha): ?Achado
    {
        if (! $this->exigeVertical || $ficha->largura === null) {
            return null;
        }

        if ($ficha->ehVertical()) {
            return Achado::ok("Vertical 9:16 ({$ficha->largura}×{$ficha->altura}).");
        }

        return Achado::atencao(
            "Vídeo {$ficha->largura}×{$ficha->altura} não é 9:16.",
            'A rede vai cortar ou colocar tarjas nas laterais. Prefira um arquivo vertical.'
        );
    }

    private function conferirVideoCodec(FichaTecnica $ficha): ?Achado
    {
        if ($ficha->codecVideo === null) {
            return null;
        }

        if (in_array($ficha->codecVideo, $this->codecsVideo, true)) {
            // ⭐ DEC-33: o vídeo passa intacto. É o que impede a perda de
            // qualidade que os concorrentes causam recodificando tudo por padrão.
            return Achado::ok("Vídeo em {$ficha->codecVideo} — passa intacto, sem perder qualidade.");
        }

        return Achado::atencao(
            "Vídeo em {$ficha->codecVideo}, que esta rede não aceita.",
            'Vamos converter só o vídeo para H.264.'
        );
    }

    private function conferirAudio(FichaTecnica $ficha): ?Achado
    {
        if (! $ficha->temAudio()) {
            return Achado::atencao(
                'O arquivo não tem áudio.',
                'Publica normalmente, mas vídeo mudo costuma render menos.'
            );
        }

        if (! in_array($ficha->codecAudio, $this->codecsAudio, true)) {
            return Achado::atencao(
                "Áudio em {$ficha->codecAudio}, que esta rede não aceita.",
                'Vamos recodificar só o áudio — a imagem fica intacta.'
            );
        }

        return Achado::ok("Áudio em {$ficha->codecAudio} — passa intacto.");
    }
}
