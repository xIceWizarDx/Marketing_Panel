<?php

namespace App\Enums;

/**
 * Estado de um destino (uma publicação numa conta).
 *
 * ⭐ DEC-31 — o status honesto é o produto. `processando` existe porque
 * **HTTP 200 não é publicação**: TikTok e YouTube aceitam o arquivo e moderam
 * depois. É PROIBIDO marcar `Publicado` sem ter relido o post na rede e obtido
 * o link. Nenhum concorrente faz isso — é o que a gente vende.
 */
enum StatusDestino: string
{
    case Pendente = 'pendente';
    case AguardandoJanela = 'aguardando_janela';
    case Enviando = 'enviando';
    case Processando = 'processando';
    case Publicado = 'publicado';
    case Falhou = 'falhou';

    /**
     * ⭐ **Esteve no ar e saiu** (DEC-148).
     *
     * ⛔ Existe porque as duas alternativas mentem. Deixar como `Publicado` diz
     * que continua no ar um post que a rede removeu — é exatamente o defeito que
     * o produto acusa nos concorrentes. E marcar `Falhou` diz que nunca subiu um
     * post que **subiu** e foi tirado depois — o que manda a pessoa publicar de
     * novo sem entender o que houve.
     *
     * ⚠️ Só a reconferência (DEC-145) leva um destino até aqui, e ela só chega
     * neste estado quando a rede **afirma** que o post não existe mais — nunca
     * por instabilidade.
     */
    case Removido = 'removido';

    public function rotulo(): string
    {
        return __("rotulos.status_destino.{$this->value}");
    }

    /** Chegou ao fim — não muda mais sozinho. */
    public function ehTerminal(): bool
    {
        return match ($this) {
            self::Publicado, self::Falhou, self::Removido => true,
            default => false,
        };
    }

    /** O motor ainda tem trabalho a fazer com este destino. */
    public function emAndamento(): bool
    {
        return ! $this->ehTerminal();
    }

    /**
     * Para onde este estado pode ir.
     *
     * Lista fechada de propósito: qualquer transição fora daqui lança exceção.
     * É o que impede o motor de "pular" da fila direto para publicado sem
     * ninguém ter conferido nada.
     *
     * @return list<self>
     */
    public function transicoesPermitidas(): array
    {
        return match ($this) {
            self::Pendente => [self::Enviando, self::AguardandoJanela, self::Falhou],
            self::AguardandoJanela => [self::Pendente, self::Falhou],
            // Aceito pela rede → `Processando`. Erro no envio → `Falhou`.
            // Falha transitória (429, timeout, 5xx) → volta pra fila.
            // ⚠️ `AguardandoJanela` também sai daqui: a cota diária só é
            // descoberta ao CHAMAR a API, e nesse momento o destino já está
            // `enviando`. Sem esta transição, cota estourada viraria exceção.
            self::Enviando => [self::Processando, self::Falhou, self::Pendente, self::AguardandoJanela],
            // Só a conciliação tira daqui: ela releu o post e sabe o desfecho.
            self::Processando => [self::Publicado, self::Falhou, self::Pendente],
            /*
             * ⭐ **Publicado deixou de ser definitivo — e a mudança é do mundo,
             * não do código** (DEC-148). Rede remove post depois de aceitar, e
             * a reconferência existe para descobrir isso.
             *
             * ⛔ Mas ele só sai para `Removido`, nunca para `Falhou`: dizer que
             * falhou o que subiu é mentir na direção oposta.
             */
            self::Publicado => [self::Removido],
            self::Falhou => [self::Pendente],
            /*
             * ⚠️ Sem volta. Um post removido pela rede não volta sozinho, e
             * republicar é outra publicação — com outro vídeo, outra data e
             * outra prova.
             */
            self::Removido => [],
        };
    }

    public function podeIrPara(self $destino): bool
    {
        return in_array($destino, $this->transicoesPermitidas(), true);
    }

    public static function paraSelecao(): array
    {
        return array_map(
            fn (self $s) => ['valor' => $s->value, 'rotulo' => $s->rotulo()],
            self::cases()
        );
    }
}
