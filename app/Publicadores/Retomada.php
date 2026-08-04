<?php

namespace App\Publicadores;

use App\Models\Destino;
use App\Services\PublicacaoService;

/**
 * O caderninho de retomada de um envio.
 *
 * Existe por causa do upload em pedaços: o YouTube devolve um endereço de
 * sessão ANTES de o arquivo subir. Se a fila reentregar o job no meio, o motor
 * precisa **retomar por esse endereço** em vez de recomeçar — senão o mesmo
 * vídeo sobe duas vezes, e publicação não tem desfazer.
 *
 * É uma peça separada de propósito: o publicador precisa **gravar** o endereço,
 * mas não pode encostar na máquina de estados. Aqui ele grava só isso, e o
 * `PublicacaoService` segue sendo o escritor único do status.
 */
class Retomada
{
    public function __construct(
        private readonly Destino $destino,
        private readonly PublicacaoService $motor,
    ) {}

    /** O endereço guardado numa tentativa anterior, se houver. */
    public function handle(): ?string
    {
        return $this->destino->handle_externo;
    }

    public function comecouAntes(): bool
    {
        return $this->handle() !== null;
    }

    /**
     * Guarda o endereço de retomada.
     *
     * ⚠️ Chamar ANTES do efeito irreversível, nunca depois: o ponto todo é ter
     * o endereço salvo caso o processo morra no meio do envio.
     */
    public function guardar(string $handle): void
    {
        $this->motor->guardarHandle($this->destino, $handle);
    }

    /** Envio concluído: o endereço não serve mais e vira lixo confuso. */
    public function limpar(): void
    {
        $this->motor->guardarHandle($this->destino, '');
    }
}
