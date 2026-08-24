<?php

namespace App\Publicadores;

use App\Enums\Plataforma;
use RuntimeException;

/**
 * Aponta cada rede para o seu publicador.
 *
 * Ponto unico: rede nova = uma linha aqui. `match` sem `default` de proposito —
 * adicionar um case no enum sem escrever o publicador quebra na hora.
 */
class RegistroDePublicadores
{
    public function para(Plataforma $plataforma): Publicador
    {
        return match ($plataforma) {
            Plataforma::Bluesky => app(PublicadorBluesky::class),
            Plataforma::Youtube => app(PublicadorYoutube::class),
            Plataforma::Facebook => app(PublicadorFacebook::class),
            Plataforma::Instagram => app(PublicadorInstagram::class),
            Plataforma::Threads => app(PublicadorThreads::class),
            Plataforma::Linkedin => app(PublicadorLinkedin::class),
            Plataforma::Tiktok => app(PublicadorTiktok::class),
            Plataforma::X => app(PublicadorX::class),
            Plataforma::Pinterest => app(PublicadorPinterest::class),
            Plataforma::Mastodon => app(PublicadorMastodon::class),
            Plataforma::Discord => app(PublicadorDiscord::class),

            // As demais entram nas fases seguintes. Erro explicito e melhor que
            // um destino que fica "na fila" para sempre sem ninguem entender.
            default => throw new RuntimeException(
                "A publicação no {$plataforma->rotulo()} ainda não está disponível."
            ),
        };
    }

    /**
     * O leitor de métricas desta rede — ou `null`, que é o caso NORMAL.
     *
     * ⛔ Nada de exceção aqui, ao contrário de `para()`. Rede sem métrica não é
     * erro de programação: das nove redes pesquisadas, sete estão bloqueadas por
     * aprovação, por dinheiro ou por cláusula de contrato, e nenhuma delas por
     * falta de código nosso (DEC-93).
     *
     * ⚠️ Também devolve `null` quando a rede não publica em geral — publicador
     * inexistente não tem o que ler.
     */
    public function leitorDe(Plataforma $plataforma): ?LeitorDeMetricas
    {
        if (! $this->disponivel($plataforma)) {
            return null;
        }

        $publicador = $this->para($plataforma);

        return $publicador instanceof LeitorDeMetricas ? $publicador : null;
    }

    /** Existe publicador escrito para esta rede? */
    public function disponivel(Plataforma $plataforma): bool
    {
        try {
            $this->para($plataforma);

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * Da pra conectar uma conta desta rede AGORA, neste servidor?
     *
     * Diferente de `disponivel()`: o publicador do YouTube existe, mas sem a
     * credencial do Google Cloud configurada o botao levaria a um erro. Botao
     * que falha e pior que botao ausente.
     */
    public function podeConectar(Plataforma $plataforma): bool
    {
        if (! $this->disponivel($plataforma)) {
            return false;
        }

        return match ($plataforma) {
            // O Bluesky usa senha de aplicativo: nao depende de configuracao
            // nossa nenhuma.
            Plataforma::Bluesky => true,
            Plataforma::Youtube => filled(config('services.google.client_id'))
                && filled(config('services.google.client_secret')),
            // Uma credencial so acende as duas: a conta do Instagram fica
            // pendurada numa Pagina do Facebook, e o login e o mesmo.
            Plataforma::Facebook, Plataforma::Instagram => filled(config('services.meta.client_id'))
                && filled(config('services.meta.client_secret')),
            /*
             * ⛔ O Threads tem credencial PRÓPRIA — o `client_id` do Facebook
             * não serve aqui (DEC-99).
             *
             * ⚠️ E ele exige mais que credencial: a rede vem BUSCAR o arquivo,
             * então sem endereço público a conexão conecta e nunca publica
             * (DEC-101). Botão que leva a isso é pior que botão ausente.
             */
            Plataforma::Threads => filled(config('services.threads.client_id'))
                && filled(config('services.threads.client_secret'))
                && self::alcancavelPelaInternet(),
            /*
             * ⚠️ O LinkedIn nao precisa de endereco publico — aqui a midia SOBE,
             * a rede nao vem buscar. O que ele precisa e da credencial propria:
             * o aplicativo e outro, no portal da LinkedIn.
             */
            Plataforma::Linkedin => filled(config('services.linkedin.client_id'))
                && filled(config('services.linkedin.client_secret')),
            // ⚠️ `client_key`, nao `client_id`: o TikTok chama assim.
            Plataforma::Tiktok => filled(config('services.tiktok.client_key'))
                && filled(config('services.tiktok.client_secret')),
            Plataforma::X => filled(config('services.x.client_id'))
                && filled(config('services.x.client_secret')),
            Plataforma::Pinterest => filled(config('services.pinterest.client_id'))
                && filled(config('services.pinterest.client_secret')),
            /*
             * ⭐ **Nao depende de credencial nenhuma nossa** (DEC-139): cada
             * servidor emite o par na hora. E a unica rede do painel que conecta
             * sem ninguem ter criado conta de desenvolvedor em lugar algum.
             */
            Plataforma::Mastodon => true,
            /*
             * ⭐ Tambem nao depende de credencial nossa: a pessoa cria o webhook
             * no proprio Discord e cola o endereco (DEC-141).
             */
            Plataforma::Discord => true,
            default => false,
        };
    }

    /** @return list<Plataforma> */
    public function plataformasConectaveis(): array
    {
        return array_values(array_filter(Plataforma::cases(), $this->podeConectar(...)));
    }

    /** @return list<Plataforma> */
    public function plataformasDisponiveis(): array
    {
        return array_values(array_filter(Plataforma::cases(), $this->disponivel(...)));
    }

    /**
     * O painel tem endereco que a internet alcanca?
     *
     * ⚠️ Conferido pelo `APP_URL`, que e o endereco que o painel usa para montar
     * links — e e ele que vai parar dentro da URL da midia que a rede busca.
     */
    private static function alcancavelPelaInternet(): bool
    {
        $maquina = parse_url((string) config('app.url'), PHP_URL_HOST) ?: '';

        return $maquina !== ''
            && ! in_array($maquina, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)
            && ! preg_match('/\.(test|local|localhost)$/i', $maquina);
    }
}
