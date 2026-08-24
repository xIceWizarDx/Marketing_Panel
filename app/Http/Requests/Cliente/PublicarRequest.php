<?php

namespace App\Http\Requests\Cliente;

use App\Support\HashtagsLimpas;
use Illuminate\Foundation\Http\FormRequest;

class PublicarRequest extends FormRequest
{
    /**
     * ⭐ O `#` cai antes da validação — mesma limpeza do grupo (DEC-152).
     *
     * ⚠️ O campo da tela já separa por `#`, mas colar uma lista pronta não
     * passa por ele. Recusar por um caractere que a pessoa não escolheu
     * escrever é recusa que ela não tem como entender.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('hashtags')) {
            $this->merge(['hashtags' => HashtagsLimpas::de($this->input('hashtags'))]);
        }
    }

    public function rules(): array
    {
        return [
            'midia' => ['required', 'string'],
            'contas' => ['required', 'array', 'min:1'],
            'contas.*' => ['string'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'legenda' => ['nullable', 'string', 'max:5000'],
            'hashtags' => ['nullable', 'array', 'max:30'],
            // Sem `#` e sem espaco: a hashtag e guardada limpa e cada rede
            // recebe do jeito que ela espera.
            'hashtags.*' => ['string', 'regex:/^[\pL\pN_]+$/u', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'midia.required' => 'Escolha o vídeo ou a imagem que vai ser publicada.',
            'contas.required' => 'Escolha pelo menos uma conta.',
            'contas.min' => 'Escolha pelo menos uma conta.',
            'hashtags.*.regex' => 'Hashtag só com letras, números e _ (sem espaço e sem #).',
        ];
    }
}
