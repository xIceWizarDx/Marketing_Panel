<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;

class ConectarBlueskyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'identificador' => ['required', 'string', 'max:255'],
            'senha_de_aplicativo' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'identificador.required' => 'Informe seu nome de usuário do Bluesky.',
            'senha_de_aplicativo.required' => 'Cole a senha de aplicativo gerada no Bluesky.',
        ];
    }
}
