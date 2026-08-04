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

            // As demais entram nas fases seguintes. Erro explicito e melhor que
            // um destino que fica "na fila" para sempre sem ninguem entender.
            default => throw new RuntimeException(
                "A publicação no {$plataforma->rotulo()} ainda não está disponível."
            ),
        };
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
}
