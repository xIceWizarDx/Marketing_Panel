<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;

/**
 * O nome de um grupo — e só ele.
 *
 * ⛔ **Nada de dono aqui, e nada de "quais canais entram".** O dono vem do
 * escopo; os canais entram depois, conectando ou movendo. Aceitar qualquer um
 * dos dois pelo formulário seria deixar o cliente escolher o que é do servidor.
 */
class GuardarGrupoRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // ⚠️ Sem `unique`: o nome não é único de propósito (DEC-69). Índice
            // único com arquivamento reclamaria de um grupo que a pessoa não
            // enxerga mais em lugar nenhum.
            'nome' => ['required', 'string', 'max:60'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nome.required' => 'Dê um nome ao grupo — é assim que você vai reconhecê-lo.',
            'nome.max' => 'Um nome mais curto cabe melhor no seletor: até 60 letras.',
        ];
    }

    public function nomeEscolhido(): string
    {
        return trim($this->string('nome')->toString());
    }
}
