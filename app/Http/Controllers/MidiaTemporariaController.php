<?php

namespace App\Http\Controllers;

use App\Models\Midia;
use App\Services\MidiaService;
use App\Support\ContextoDoUsuario;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ⭐ **O único endereço do produto que serve arquivo SEM SESSÃO.**
 *
 * Ele existe por uma exigência de fora: o Threads não aceita envio de arquivo —
 * ele recebe uma **URL** e vai buscar a mídia sozinho. Quem busca é um servidor
 * da Meta, que não tem login no painel e nunca terá.
 *
 * ⛔ **Por isso a assinatura é a trava inteira.** Não há dono conferido, não há
 * escopo, não há middleware de papel: se o endereço for adivinhável, ele é o
 * arquivo de qualquer cliente. Toda a segurança mora em três coisas juntas —
 * assinatura que não se forja, prazo curto, e um endereço que só serve o
 * arquivo.
 *
 * ⚠️ **A URL é do ENVIO, não da mídia** (DEC-100). Ela nasce no instante em que
 * um destino vai subir, vive minutos e morre. Um endereço permanente para o
 * arquivo de quem paga é vazamento com data marcada — e seria exatamente o que a
 * escolha do Login do Facebook evitou no Instagram.
 */
class MidiaTemporariaController extends Controller
{
    /**
     * Quanto tempo o endereço vive.
     *
     * ⚠️ Curto de propósito, e mesmo assim com folga: a Meta busca o arquivo
     * durante o processamento do contêiner, que a documentação estima em ~30
     * segundos. Quinze minutos cobrem fila cheia e rede lenta sem transformar o
     * endereço em algo que sobreviva ao envio.
     */
    private const MINUTOS_DE_VIDA = 15;

    public function __construct(private readonly MidiaService $midias) {}

    /**
     * O endereço que a rede vai buscar.
     *
     * ⚠️ Gerado **na hora do envio**, nunca guardado: URL assinada guardada em
     * banco é URL permanente com outro nome.
     */
    public static function enderecoDe(Midia $midia): string
    {
        return URL::temporarySignedRoute(
            'midias.temporaria',
            now()->addMinutes(self::MINUTOS_DE_VIDA),
            ['ulid' => $midia->ulid],
        );
    }

    /**
     * Entrega o arquivo, e **só** o arquivo.
     *
     * ⛔ Sem nome original no cabeçalho: o nome que a pessoa deu ao arquivo é
     * dado dela, e este endereço é público por construção. `inline` sem nome
     * entrega os bytes e mais nada.
     *
     * ⚠️ A busca corre **sem escopo de dono**, e tem que correr: não há usuário
     * na sessão para o escopo usar, e com ele a consulta lançaria exceção. Quem
     * autoriza aqui é a assinatura, conferida pelo middleware antes de chegar
     * neste método.
     */
    public function __invoke(string $ulid): StreamedResponse
    {
        $midia = ContextoDoUsuario::semEscopo(
            fn () => Midia::where('ulid', $ulid)->first()
        );

        // ⚠️ 404 e não 403: 403 confirmaria que o identificador existe.
        abort_if(! $midia || ! $midia->caminho, 404);

        return Storage::disk($this->midias->disco())->response(
            $midia->caminho,
            null,
            [
                'X-Content-Type-Options' => 'nosniff',
                'Content-Type' => $midia->mime_type,
                // ⛔ Nada de cache: endereço que expira em 15 minutos não pode
                // deixar cópia em intermediário nenhum.
                'Cache-Control' => 'no-store, private',
                // ⛔ Fora de buscador. Ele não deveria chegar aqui — mas se
                // chegar, não indexa.
                'X-Robots-Tag' => 'noindex, nofollow',
            ]
        );
    }
}
