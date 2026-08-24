<?php

namespace App\Support;

use App\Enums\Plataforma;
use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Publicadores\RegistroDePublicadores;

/**
 * ⭐ **O total — e as três ressalvas que o tornam honesto** (DEC-146).
 *
 * ⛔ Somar visualização de redes diferentes é somar **unidades diferentes**: o
 * Facebook conta *"3 segundos ou até o fim, o que vier primeiro"*, o TikTok
 * conta de outro jeito, o YouTube de outro. O número que sai daqui **não serve
 * para comparar redes** — e usá-lo assim faz a rede que conta mais frouxo
 * parecer a melhor.
 *
 * ⚠️ **Mas esconder o total também é errado.** A pessoa quer sentir o tamanho, e
 * a soma responde bem a *"estou crescendo?"*: a impureza é a mesma mês a mês,
 * então a direção fica certa mesmo com as unidades misturadas.
 *
 * As três ressalvas, e nenhuma é opcional:
 *
 * 1. o número é **soma bruta**, e a tela diz isso com todas as letras;
 * 2. ele diz **de quantas redes veio** — sem isso, uma rede que não respondeu
 *    hoje vira queda de desempenho que não aconteceu;
 * 3. a comparação entre redes acontece **do lado**, cada uma com o número dela.
 */
final class AlcanceSomado
{
    private function __construct(
        /** A soma bruta das visualizações lidas. `null` = nenhuma leitura ainda. */
        public readonly ?int $visualizacoes,
        /** Quantas redes de fato responderam. */
        public readonly int $redesQueResponderam,
        /** Em quantas redes há post publicado que PODERIA responder. */
        public readonly int $redesQuePodiamResponder,
    ) {}

    public static function doDono(RegistroDePublicadores $publicadores): self
    {
        /*
         * ⛔ **`Destino` NÃO tem escopo de dono** — quem tem são `ContaSocial` e
         * `Publicacao`. Consultar `Destino::query()` direto varre o banco
         * inteiro, de todos os clientes.
         *
         * ⚠️ Isso quase virou vazamento aqui: a primeira versão somava
         * visualização de destino alheio. Quem barrou foi o escopo da conta, e
         * ele barrou **quebrando** — a relação vinha nula e o código estourava.
         * Barulho é melhor que silêncio, mas depender disso é sorte.
         *
         * ⭐ O `whereIn` abaixo é o que aplica o escopo, e é a mesma forma que o
         * `ResumoDoPainel` usa: a subconsulta de contas já nasce filtrada pelo
         * dono corrente.
         */
        $destinos = Destino::query()
            ->whereIn('conta_social_id', ContaSocial::query()->select('id'))
            ->whereIn('status', [StatusDestino::Publicado->value, StatusDestino::Removido->value])
            ->with('contaSocial:id,plataforma')
            ->get();

        if ($destinos->isEmpty()) {
            return new self(null, 0, 0);
        }

        $porRede = $destinos->groupBy(fn (Destino $d) => $d->contaSocial->plataforma->value);

        $soma = null;
        $responderam = 0;
        $podiam = 0;

        foreach ($porRede as $chave => $daRede) {
            $rede = Plataforma::from((string) $chave);

            /*
             * ⛔ "Podia responder" é ter LEITOR escrito — não é ter post. Uma
             * rede sem leitor nunca vai entrar na soma, e contá-la no
             * denominador faria a frase dizer "3 de 8" para sempre, como se
             * cinco redes estivessem em silêncio por defeito.
             */
            if ($publicadores->leitorDe($rede) === null) {
                continue;
            }

            $podiam++;

            $lidas = $daRede->filter(fn (Destino $d) => $d->visualizacoes !== null);

            if ($lidas->isEmpty()) {
                continue;
            }

            $responderam++;
            $soma = (int) $soma + (int) $lidas->sum('visualizacoes');
        }

        return new self($soma, $responderam, $podiam);
    }

    /**
     * ⭐ A frase da segunda ressalva — ou `null` quando não há o que ressalvar.
     *
     * ⚠️ Ela só aparece quando **falta** alguém: dizer "somando 3 de 3" toda vez
     * seria ruído, e ruído é o que faz a pessoa parar de ler o aviso no dia em
     * que ele importa.
     */
    public function fraseDasRedes(): ?string
    {
        if ($this->redesQuePodiamResponder === 0 || $this->redesQueResponderam >= $this->redesQuePodiamResponder) {
            return null;
        }

        $faltam = $this->redesQuePodiamResponder - $this->redesQueResponderam;

        return $faltam === 1
            ? "Somando {$this->redesQueResponderam} de {$this->redesQuePodiamResponder} redes — uma ainda não respondeu hoje."
            : "Somando {$this->redesQueResponderam} de {$this->redesQuePodiamResponder} redes — {$faltam} ainda não responderam hoje.";
    }

    /** @return array{visualizacoes: ?int, nota: string, redes: ?string} */
    public function paraTela(): array
    {
        return [
            'visualizacoes' => $this->visualizacoes,
            // ⛔ A primeira ressalva, e ela é fixa: o número é bruto, sempre.
            'nota' => 'Soma bruta — cada rede conta visualização do seu jeito. '.
                'Serve para ver movimento, não para comparar redes.',
            'redes' => $this->fraseDasRedes(),
        ];
    }
}
