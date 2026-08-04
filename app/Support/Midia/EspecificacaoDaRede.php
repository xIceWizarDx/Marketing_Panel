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
                // ⭐ O Bluesky conta GRAFEMAS: emoji de família é 1, não 7.
                texto: new LimitesDeTexto(legenda: 300, medidaDaLegenda: Medida::Grafemas),
                contêineresAceitos: ['video/mp4'],
            ),

            // Doc 10: MP4, 3s a 30min, ate 500 MB, 9:16 aceito. Publica no
            // PERFIL — a Pagina e barreira alta e entra depois.
            Plataforma::Linkedin => new self(
                $plataforma, 3, 600, 500 * 1024 * 1024,
                ['h264'], ['aac'],
                aceitaImagem: true, exigeVertical: false,
                texto: new LimitesDeTexto(legenda: 3000),
            ),

            // Doc 10: os limites mais generosos das quatro da Meta (5min / 1GB).
            Plataforma::Threads => new self(
                $plataforma, 3, 300, 1024 * 1024 * 1024,
                ['h264', 'hevc'], ['aac'],
                aceitaImagem: true, exigeVertical: false,
                texto: new LimitesDeTexto(legenda: 500),
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
                aceitaImagem: true,
                // Proporção aceita vai de 0,01:1 a 10:1 — vertical é recomendado,
                // não exigido. Recusar horizontal aqui seria regra nossa.
                exigeVertical: false,
                texto: new LimitesDeTexto(legenda: 2200),
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
                aceitaImagem: true,
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
                texto: new LimitesDeTexto(legenda: 2200),
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

        $achados = [
            $l->conferir((string) $titulo, $l->titulo, $l->medidaDoTitulo, 'Título'),
            $l->conferir((string) $legenda, $l->legenda, $l->medidaDaLegenda, 'Legenda'),
            // O orçamento das hashtags é do conjunto, com os separadores — é
            // assim que o YouTube conta.
            $l->conferir($this->juntarHashtags($hashtags), $l->hashtags, $l->medidaDasHashtags, 'Hashtags'),
        ];

        return array_values(array_filter($achados));
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
