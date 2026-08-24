<?php

namespace App\Support;

use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;

/**
 * ⭐ **A comparação que resolve o problema das unidades** (DEC-147).
 *
 * ⛔ Comparar 900 visualizações do YouTube com 900 do TikTok é comparar réguas
 * diferentes: cada rede define "visualização" do seu jeito, e a que conta mais
 * frouxo sempre ganha (DEC-146).
 *
 * ⭐ Comparar um post do TikTok com **a média dos seus posts no TikTok** não tem
 * esse problema — é a mesma régua dos dois lados. E é assim que a pergunta que
 * importa fica respondível: *"esse corte foi melhor em qual rede?"* vira *"foi
 * acima da média dele no TikTok e abaixo no Reels"*.
 *
 * ⚠️ **E só publicamos isso com base.** Com dois posts, qualquer um está "acima"
 * ou "abaixo" da média por acaso — afirmar tendência ali seria inventar
 * significado. Sem base, a resposta é silêncio.
 */
final class MediaPorRede
{
    /**
     * ⚠️ Três posts é o mínimo para a palavra "média" significar alguma coisa.
     * Com dois, ela é só "o outro".
     */
    private const BASE_MINIMA = 3;

    /** @param array<string, float> $medias por plataforma */
    private function __construct(private readonly array $medias) {}

    public static function doDono(): self
    {
        /*
         * ⛔ `Destino` **não tem escopo de dono** — quem tem são `ContaSocial` e
         * `Publicacao`. Sem este `whereIn`, a média sairia do banco inteiro, de
         * todos os clientes.
         */
        $destinos = Destino::query()
            ->whereIn('conta_social_id', ContaSocial::query()->select('id'))
            ->whereIn('status', [StatusDestino::Publicado->value, StatusDestino::Removido->value])
            ->whereNotNull('visualizacoes')
            ->with('contaSocial:id,plataforma')
            ->get();

        $medias = [];

        foreach ($destinos->groupBy(fn (Destino $d) => $d->contaSocial->plataforma->value) as $rede => $daRede) {
            if ($daRede->count() < self::BASE_MINIMA) {
                continue;
            }

            $medias[(string) $rede] = (float) $daRede->avg('visualizacoes');
        }

        return new self($medias);
    }

    /**
     * Como este post se saiu perto da média **da rede dele** — ou `null`.
     *
     * ⚠️ Devolve a palavra, não o número: *"acima"* é o que a pessoa usa para
     * decidir. A porcentagem exata daria uma falsa precisão sobre uma amostra
     * que costuma ser pequena.
     */
    public function comparar(Destino $destino): ?string
    {
        $rede = $destino->contaSocial->plataforma->value;

        if ($destino->visualizacoes === null || ! isset($this->medias[$rede])) {
            return null;
        }

        $media = $this->medias[$rede];

        if ($media <= 0.0) {
            return null;
        }

        /*
         * ⚠️ Uma faixa de 15% no meio é chamada de "na média" de propósito.
         * Sem ela, um post 2% acima viraria "acima da média" — e a palavra
         * perderia o sentido justamente para quem confia nela.
         */
        $razao = $destino->visualizacoes / $media;

        return match (true) {
            $razao >= 1.15 => 'acima da sua média nesta rede',
            $razao <= 0.85 => 'abaixo da sua média nesta rede',
            default => 'na média desta rede',
        };
    }
}
