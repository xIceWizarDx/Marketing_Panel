<?php

namespace App\Http\Requests\Cliente;

use App\Enums\TipoMidia;
use App\Support\Midia\LimiteDeEnvio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarMidiaRequest extends FormRequest
{
    public function rules(): array
    {
        // Mesmo teto que a tela mostra — validar por um número maior
        // deixaria passar arquivo que o PHP vai recusar depois.
        $tetoKb = LimiteDeEnvio::megabytes() * 1024;

        return [
            'tipo' => ['required', Rule::enum(TipoMidia::class)],

            'arquivo' => [
                'required',
                'file',
                "max:{$tetoKb}",
                // `mimetypes` lê o CONTEÚDO do arquivo (finfo). A regra `mimes`,
                // que parece equivalente, olha a EXTENSÃO — e extensão é escolhida
                // por quem envia. Um `.mp4` pode ser qualquer coisa por dentro.
                'mimetypes:'.implode(',', $this->mimesAceitos()),
            ],
        ];
    }

    public function messages(): array
    {
        $teto = LimiteDeEnvio::megabytes();

        return [
            'arquivo.required' => 'Escolha um arquivo.',
            'arquivo.max' => "O arquivo passa de {$teto} MB, que é o limite das redes.",
            'arquivo.mimetypes' => $this->tipoEscolhido() === TipoMidia::Video
                ? 'Envie um vídeo MP4 ou MOV.'
                : 'Envie uma imagem JPEG — é o formato que todas as redes aceitam.',
        ];
    }

    public function tipoEscolhido(): TipoMidia
    {
        return TipoMidia::tryFrom((string) $this->input('tipo')) ?? TipoMidia::Video;
    }

    /** @return list<string> */
    private function mimesAceitos(): array
    {
        return $this->tipoEscolhido()->mimesAceitos();
    }
}
