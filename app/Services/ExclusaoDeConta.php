<?php

namespace App\Services;

use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Models\Scopes\EscopoDoUsuario;
use App\Models\Usuario;
use App\Support\ContextoDoUsuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Apaga uma conta inteira — **na ordem que as chaves estrangeiras exigem**.
 *
 * ⚠️ Todas as tabelas de cliente apontam para `usuarios` com `restrict`, de
 * propósito: apagar cliente com dado tem que ser decisão explícita, nunca
 * cascata silenciosa. A contrapartida é que `$usuario->delete()` sozinho
 * **estoura** para qualquer pessoa que tenha usado o produto — e quem usou é
 * justamente quem mais precisa conseguir sair.
 *
 * ⛔ Roda com o dono no contexto, nunca em `semEscopo`: escrita em massa sem
 * filtro de dono aqui apagaria a plataforma inteira.
 */
class ExclusaoDeConta
{
    /**
     * ⚠️ **Chamar com a sessão JÁ encerrada.**
     *
     * `Auth::logout()` depois daqui **ressuscita a conta**: o guard cicla o
     * *remember token* do usuário que ainda tem em mãos e chama `save()` — e
     * `save()` num model já apagado vira INSERT. A pessoa some, volta com id
     * novo, e o gatilho de criação ainda lhe dá um grupo em branco.
     *
     * É por isso que o dono vem pelo contexto: sem sessão, é a única forma de o
     * escopo continuar valendo para as consultas abaixo.
     *
     * O registro de acesso NÃO é apagado — `logs_impersonacao` usa
     * `nullOnDelete` + cópia do ULID, então a pessoa some e o evento fica
     * anonimizado. Log que bloqueia exclusão já foi defeito real aqui.
     */
    public function apagar(Usuario $usuario): void
    {
        ContextoDoUsuario::definir($usuario);

        try {
            $caminhos = DB::transaction(function () use ($usuario) {
                // Guardado antes de apagar: depois não sobra linha de onde tirar
                // o caminho do arquivo.
                $caminhos = Midia::query()
                    ->get(['caminho', 'miniatura'])
                    ->flatMap(fn (Midia $midia) => [$midia->caminho, $midia->miniatura])
                    ->filter()
                    ->values()
                    ->all();

                // Ordem obrigatória, de baixo para cima: tentativas e credenciais
                // caem em cascata com o pai, mas destino segura a conta social e
                // publicação segura a mídia — as duas `restrict`.
                Destino::whereIn('publicacao_id', Publicacao::query()->select('id'))->delete();
                Publicacao::query()->delete();
                ContaSocial::query()->delete();
                Midia::query()->delete();

                // O grupo é o último: tudo o mais apontava para ele. `withTrashed`
                // porque grupo arquivado continua sendo linha no banco.
                Grupo::withoutGlobalScope(EscopoDoUsuario::class)
                    ->withTrashed()
                    ->where('usuario_id', $usuario->id)
                    ->forceDelete();

                $usuario->delete();

                return $caminhos;
            });
        } finally {
            ContextoDoUsuario::esquecer();
        }

        $this->apagarArquivos($caminhos);
    }

    /**
     * Os arquivos saem DEPOIS do commit.
     *
     * ⚠️ Apagar dentro da transação e ela voltar atrás deixaria o pior dos dois
     * mundos: as linhas de volta e os vídeos já destruídos. Falhar aqui deixa
     * arquivo órfão — chato, e recuperável.
     *
     * @param  list<string>  $caminhos
     */
    private function apagarArquivos(array $caminhos): void
    {
        if ($caminhos === []) {
            return;
        }

        try {
            Storage::disk(config('midia.disco'))->delete($caminhos);
        } catch (\Throwable $erro) {
            Log::warning('Conta apagada, mas sobraram arquivos no disco', [
                'quantos' => count($caminhos),
                'motivo' => $erro->getMessage(),
            ]);
        }
    }
}
