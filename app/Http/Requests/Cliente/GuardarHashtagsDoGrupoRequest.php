<?php

namespace App\Http\Requests\Cliente;

use App\Support\HashtagsLimpas;
use Illuminate\Foundation\Http\FormRequest;

/**
 * As hashtags que este grupo já traz escritas ao compor (DEC-152).
 *
 * ⛔ **Mesma régua do compositor, de propósito.** Aceitar aqui uma hashtag que
 * o publicar recusaria criaria a pior sequência possível: a pessoa salva no
 * grupo, o painel diz que deu certo, e o erro só aparece quando ela tenta
 * publicar — num campo que ela nem escreveu.
 */
class GuardarHashtagsDoGrupoRequest extends FormRequest
{
    /** ⭐ O `#` cai antes da validação — mesma limpeza do publicar (DEC-152). */
    protected function prepareForValidation(): void
    {
        $this->merge(['hashtags' => HashtagsLimpas::de($this->input('hashtags'))]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'hashtags' => ['present', 'array', 'max:30'],
            // Sem `#` e sem espaço: guardamos limpo e cada rede recebe do jeito
            // que ela espera.
            'hashtags.*' => ['string', 'regex:/^[\pL\pN_]+$/u', 'max:60'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'hashtags.max' => 'São até 30 hashtags — é o teto que as redes aceitam.',
            'hashtags.*.regex' => 'Hashtag só com letras, números e _ (sem espaço e sem #).',
        ];
    }

    /**
     * A lista validada — ou `null` quando não sobrou nenhuma.
     *
     * ⚠️ Lista vazia vira `null` para o grupo ficar num estado só quando não
     * tem hashtag: `[]` e `null` significando a mesma coisa é como nasce um
     * `if` que esquece um dos dois.
     *
     * @return list<string>|null
     */
    public function hashtagsEscolhidas(): ?array
    {
        /** @var list<string> $limpas */
        $limpas = $this->validated('hashtags', []);

        return $limpas === [] ? null : $limpas;
    }
}
