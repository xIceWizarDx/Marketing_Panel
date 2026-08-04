<?php

namespace App\Services;

use App\Enums\TipoMidia;
use App\Exceptions\EscopoDeUsuarioIndefinido;
use App\Models\Midia;
use App\Models\Usuario;
use App\Support\ContextoDoUsuario;
use App\Support\Midia\Laudo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Guarda o arquivo e registra a mídia.
 *
 * Escritor único: nenhum controller grava arquivo nem cria `Midia` por conta
 * própria. Assim as regras de segurança de arquivo (0.M Camada 5) vivem em um
 * lugar só, onde dá pra revisar.
 */
class MidiaService
{
    public function __construct(
        private readonly InspetorDeMidia $inspetor,
        private readonly GeradorDeMiniatura $miniaturas,
    ) {}

    /**
     * ⭐ Uma pasta por dono — arquivo de cliente nunca encosta no de outro.
     *
     * Sem isso, os arquivos de todo mundo ficam no mesmo diretório e o
     * isolamento existe **só** no banco. Basta um bug de caminho para um cliente
     * alcançar o vídeo de outro, e é o tipo de falha que ninguém percebe até
     * acontecer.
     *
     * Com a pasta separada, três coisas ficam simples:
     *   - **apagar a conta** apaga tudo dela, sem varrer registro por registro
     *     (LGPD: sobra de arquivo órfão é dado pessoal que ninguém enxerga);
     *   - **backup e mudança de servidor** movem um cliente por vez;
     *   - o diretório não vira um saco com milhares de arquivos misturados.
     *
     * ⚠️ **ULID, nunca o `id` sequencial.** O caminho pode vazar num log ou numa
     * mensagem de erro, e o sequencial entrega quantos clientes existem e quem
     * chegou primeiro.
     */
    private function pastaDoDono(): string
    {
        $dono = ContextoDoUsuario::id();

        if (! $dono) {
            // Não existe upload sem dono. Gravar em pasta genérica seria criar
            // exatamente o arquivo órfão que este desenho evita.
            throw new EscopoDeUsuarioIndefinido(
                'Não dá para guardar mídia sem saber de quem ela é.'
            );
        }

        return (string) Usuario::withoutGlobalScopes()->whereKey($dono)->value('ulid');
    }

    public function guardar(UploadedFile $arquivo, TipoMidia $tipo): Midia
    {
        // ⭐ A assinatura do CONTEÚDO, não do nome (DEC-58).
        //
        // Serve a duas coisas: reencontrar a mídia quando a pessoa reenviar o
        // mesmo vídeo — mesmo renomeado, mesmo de outra pasta — e provar que o
        // que foi para duas redes era byte por byte o mesmo arquivo.
        //
        // `hash_file` lê em blocos: não carrega 300 MB na memória.
        $assinatura = hash_file('sha256', $arquivo->getRealPath());

        $jaConhecida = $this->reencontrar($assinatura);

        if ($jaConhecida) {
            // Mesmo conteúdo que já vive aqui: devolve o registro existente com
            // todo o histórico. Duplicar criaria duas verdades sobre um arquivo.
            return $this->devolverAoAcervo($jaConhecida, $arquivo);
        }

        // Nome gerado por nós. O nome enviado pelo usuário nunca vira caminho:
        // é por aí que entram `../` e nomes que o servidor trataria como script.
        $nomeNoDisco = Str::ulid().'.'.$this->extensaoSegura($arquivo);
        $caminho = 'midias/'.$this->pastaDoDono().'/'.date('Y/m').'/'.$nomeNoDisco;

        Storage::disk($this->disco())->putFileAs(dirname($caminho), $arquivo, basename($caminho));

        $laudo = $this->inspetor->laudar(
            Storage::disk($this->disco())->path($caminho),
            $tipo
        );

        // ⚠️ Só para vídeo: imagem já é a própria miniatura, e gerar uma cópia
        // menor dela seria guardar duas vezes a mesma coisa.
        //
        // Nunca lança: mídia sem miniatura é aceitável, envio recusado por causa
        // de uma imagem de apoio, não.
        $miniatura = $tipo === TipoMidia::Video
            ? $this->miniaturas->gerar($caminho, $this->disco())
            : null;

        try {
            return DB::transaction(fn () => Midia::create([
                'tipo' => $tipo,
                'miniatura' => $miniatura,
                // Guardado só para exibir; nunca usado como caminho.
                'nome_original' => $this->nomeExibivel($arquivo),
                'caminho' => $caminho,
                'assinatura' => $assinatura,
                // Descoberto pelo conteúdo do arquivo, não pela extensão enviada.
                'mime_type' => $arquivo->getMimeType(),
                'tamanho_bytes' => $arquivo->getSize(),
                'duracao_segundos' => $laudo->ficha->duracaoSegundos !== null
                    ? (int) round($laudo->ficha->duracaoSegundos)
                    : null,
                'largura' => $laudo->ficha->largura,
                'altura' => $laudo->ficha->altura,
                'laudo' => $laudo->paraArray(),
            ]));
        } catch (\Throwable $e) {
            // Falhou o registro? o arquivo não pode ficar no disco sem dono —
            // vira lixo que ninguém enxerga e ninguém limpa. A miniatura vai
            // junto, senão ela é que fica órfã.
            Storage::disk($this->disco())->delete(array_filter([$caminho, $miniatura]));

            throw $e;
        }
    }

    /**
     * Já existe uma mídia com este conteúdo?
     *
     * ⚠️ **Dentro do escopo do dono**, sempre. Arquivo idêntico de outro cliente
     * é outro registro: reaproveitar cruzaria dado entre contas, que é a única
     * coisa que este projeto não pode errar.
     */
    private function reencontrar(string $assinatura): ?Midia
    {
        return Midia::where('assinatura', $assinatura)->first();
    }

    /**
     * Devolve o arquivo a um registro que já existia.
     *
     * Acontece em dois casos: envio repetido por engano (o arquivo ainda está
     * aqui, então nada muda) e reenvio depois de o original ter sido liberado —
     * aí o registro volta a ser publicável, com publicações, laudo e provas
     * intactos.
     */
    private function devolverAoAcervo(Midia $midia, UploadedFile $arquivo): Midia
    {
        if ($midia->temArquivo()) {
            return $midia;
        }

        Storage::disk($this->disco())->putFileAs(
            dirname($midia->caminho),
            $arquivo,
            basename($midia->caminho)
        );

        $midia->forceFill(['arquivo_removido_em' => null])->save();

        return $midia;
    }

    public function remover(Midia $midia): void
    {
        // ⚠️ A miniatura sai junto. Esquecê-la deixaria um arquivo sem dono no
        // disco — invisível na tela, e fora do alcance de qualquer limpeza.
        $arquivos = array_filter([$midia->caminho, $midia->miniatura]);

        DB::transaction(function () use ($midia) {
            $midia->delete();
        });

        // Só depois de a linha sair: se o disco falhar, sobra arquivo órfão
        // (recuperável); se fosse ao contrário, sobraria registro apontando pro
        // vazio, que quebra a tela do cliente.
        Storage::disk($this->disco())->delete($arquivos);
    }

    /** Refaz o laudo — usado quando o `ffprobe` só ficou disponível depois. */
    public function relaudar(Midia $midia): Laudo
    {
        $laudo = $this->inspetor->laudar(
            Storage::disk($this->disco())->path($midia->caminho),
            $midia->tipo
        );

        $midia->update([
            'laudo' => $laudo->paraArray(),
            'duracao_segundos' => $laudo->ficha->duracaoSegundos !== null
                ? (int) round($laudo->ficha->duracaoSegundos)
                : $midia->duracao_segundos,
            'largura' => $laudo->ficha->largura ?? $midia->largura,
            'altura' => $laudo->ficha->altura ?? $midia->altura,
        ]);

        return $laudo;
    }

    public function disco(): string
    {
        return config('midia.disco');
    }

    /** Extensão derivada do CONTEÚDO, nunca do nome enviado. */
    private function extensaoSegura(UploadedFile $arquivo): string
    {
        return match ($arquivo->getMimeType()) {
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'image/jpeg' => 'jpg',
            // Sem correspondência conhecida, o arquivo fica sem extensão: é
            // servido por rota assinada, então extensão não faz falta nenhuma.
            default => 'bin',
        };
    }

    /** Nome que a pessoa vê no compositor — só o nome, sem caminho. */
    private function nomeExibivel(UploadedFile $arquivo): string
    {
        return Str::limit(basename($arquivo->getClientOriginalName()), 120, '');
    }
}
