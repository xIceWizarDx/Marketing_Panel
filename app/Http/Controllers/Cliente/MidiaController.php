<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\GuardarMidiaRequest;
use App\Models\Midia;
use App\Services\MidiaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MidiaController extends Controller
{
    public function __construct(
        private readonly MidiaService $midias,
    ) {}

    public function salvar(GuardarMidiaRequest $request): RedirectResponse
    {
        $midia = $this->midias->guardar($request->file('arquivo'), $request->tipoEscolhido());

        $aviso = $midia->laudo['disponivel'] ?? false
            ? null
            : 'O arquivo foi salvo, mas não conseguimos analisá-lo agora. A análise pode ser refeita depois.';

        /*
         * ⭐ Volta para o compositor exatamente de onde saiu — inclusive quando
         * é uma republicação (`/publicar/{publicacao}`), que traz o texto
         * pronto. Mandar sempre para `/publicar` jogaria esse texto fora.
         *
         * `midiaEnviada` faz o arquivo já nascer escolhido: ninguém envia um
         * vídeo para depois procurá-lo numa lista — e lista não existe mais
         * (DEC-60).
         */
        return back(fallback: route('publicar'))
            ->with('midiaEnviada', $midia->ulid)
            ->with('aviso', $aviso);
    }

    /**
     * Entrega a miniatura.
     *
     * Rota separada do arquivo original de propósito: a miniatura é pedida por
     * toda a lista de publicações de uma vez, e o vídeo inteiro só quando
     * alguém manda tocar a prévia.
     * Servir os dois pelo mesmo caminho faria o navegador baixar megabytes para
     * desenhar uma grade.
     *
     * ⚠️ O dono é conferido pelo escopo, igual ao original — miniatura também é
     * conteúdo do cliente.
     */
    public function miniatura(string $ulid): StreamedResponse
    {
        $midia = Midia::where('ulid', $ulid)->firstOrFail();

        abort_if(! $midia->miniatura, 404);

        return Storage::disk($this->midias->disco())->response(
            $midia->miniatura,
            null,
            [
                'X-Content-Type-Options' => 'nosniff',
                'Content-Type' => 'image/jpeg',
                // Conteúdo imutável: o nome tem ULID, então mudar a imagem cria
                // outro endereço. Cache longo economiza pedido a cada abertura.
                'Cache-Control' => 'private, max-age=604800',
            ]
        );
    }

    /**
     * Entrega o arquivo.
     *
     * Único caminho até o conteúdo: o disco fica fora da raiz pública, então não
     * existe URL direta. Aqui o dono é conferido pelo escopo antes de qualquer
     * byte sair.
     */
    public function baixar(string $ulid): StreamedResponse
    {
        $midia = Midia::where('ulid', $ulid)->firstOrFail();

        return Storage::disk($this->midias->disco())->response(
            $midia->caminho,
            $midia->nome_original,
            [
                // Impede o navegador de interpretar o arquivo como outra coisa.
                'X-Content-Type-Options' => 'nosniff',
                'Content-Type' => $midia->mime_type,
            ]
        );
    }
}
