<?php

namespace App\Services;

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Support\RegistroDeSeguranca;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Conecta e desconecta contas de rede social.
 *
 * Escritor único das credenciais: nenhum controller grava token direto. Assim a
 * regra "token nunca sai daqui" vive em um lugar só.
 */
class ContaSocialService
{
    /**
     * Conecta uma conta do Bluesky.
     *
     * O Bluesky usa **senha de aplicativo** — a pessoa gera nas configurações da
     * conta e pode revogar quando quiser, sem trocar a senha principal. É o que
     * dispensa auditoria e permite publicar de verdade desde já (DEC-29).
     *
     * @throws ValidationException
     */
    public function conectarBluesky(string $identificador, string $senhaDeAplicativo): ContaSocial
    {
        $identificador = ltrim(trim($identificador), '@');

        // Confere ANTES de guardar: senha errada salva vira conta que parece
        // conectada e falha só na hora de publicar.
        $sessao = $this->abrirSessaoBluesky($identificador, $senhaDeAplicativo);

        return DB::transaction(function () use ($identificador, $senhaDeAplicativo, $sessao) {
            $conta = ContaSocial::updateOrCreate(
                [
                    'plataforma' => Plataforma::Bluesky,
                    'identificador_externo' => $sessao['did'],
                ],
                [
                    'nome_exibicao' => $sessao['handle'] ?? $identificador,
                    'status' => StatusConta::Ativa,
                    'status_detalhe' => null,
                ]
            );

            // `updateOrCreate` na credencial e não `create`: reconectar a mesma
            // conta troca a senha guardada em vez de estourar por chave única.
            $conta->credencial()->updateOrCreate([], [
                'access_token' => $senhaDeAplicativo,
                'refresh_token' => null,
                // Senha de aplicativo não expira sozinha — vale até ser revogada.
                'expira_em' => null,
                'escopos' => ['publicar'],
            ]);

            RegistroDeSeguranca::registrar('rede_conectada', [
                'plataforma' => Plataforma::Bluesky->value,
                'conta_ulid' => $conta->ulid,
            ]);

            return $conta;
        });
    }

    /**
     * Desconecta: apaga a credencial e o dado do titular, preservando a linha.
     *
     * ⚠️ A linha nunca é apagada — o histórico de publicações aponta para ela, e
     * apagar levaria junto a prova de entrega do que já subiu.
     *
     * @return string o nome que a conta tinha, para a mensagem de confirmação
     */
    public function desconectar(ContaSocial $conta): string
    {
        // Guardado ANTES: logo abaixo ele é substituído pelo apelido anônimo.
        $nome = $conta->nome_exibicao;

        DB::transaction(function () use ($conta) {
            $conta->credencial?->delete();

            $conta->forceFill([
                'status' => StatusConta::Desconectada,
                'status_detalhe' => 'Desconectada por você.',
                // ⚠️ Exigência da política do YouTube: ao revogar, o dado do
                // titular tem que sair em até 7 dias. Apagamos na hora.
                //
                // A LINHA sobrevive porque o histórico de publicações aponta
                // para ela — mesma solução do DEC-44: **dado pessoal se apaga,
                // registro de entrega sobrevive anonimizado.**
                'nome_exibicao' => $this->apelidoAnonimo($conta),
                'avatar_url' => null,
            ])->save();
        });

        RegistroDeSeguranca::registrar('rede_desconectada', [
            'plataforma' => $conta->plataforma->value,
            'conta_ulid' => $conta->ulid,
        ]);

        return $nome;
    }

    /**
     * Nome que fica no lugar do dado do titular depois de desconectar.
     *
     * Mantém a linha reconhecível no histórico sem guardar dado pessoal — o
     * ULID é opaco: sem a conta, não identifica ninguém.
     */
    private function apelidoAnonimo(ContaSocial $conta): string
    {
        return $conta->plataforma->rotulo().' · conta removida';
    }

    /** Marca que a conexão quebrou — alimenta o semáforo (DEC-32). */
    public function marcarComProblema(ContaSocial $conta, string $motivo): void
    {
        $conta->forceFill([
            'status' => StatusConta::Erro,
            'status_detalhe' => $motivo,
        ])->save();
    }

    /**
     * @return array{did: string, handle: string|null}
     *
     * @throws ValidationException
     */
    private function abrirSessaoBluesky(string $identificador, string $senha): array
    {
        try {
            $resposta = Http::asJson()
                ->timeout(15)
                ->post('https://bsky.social/xrpc/com.atproto.server.createSession', [
                    'identifier' => $identificador,
                    'password' => $senha,
                ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'identificador' => 'Não conseguimos falar com o Bluesky agora. Tente de novo em instantes.',
            ]);
        }

        if (! $resposta->successful()) {
            // Mensagem em português, sem devolver o erro cru da rede — ele fala
            // de "identifier" e "DID", que não significam nada para quem lê.
            throw ValidationException::withMessages([
                'identificador' => 'O Bluesky não aceitou esse usuário e senha de aplicativo. '.
                    'Confira o nome de usuário completo (com .bsky.social) e gere uma senha de aplicativo nova.',
            ]);
        }

        return [
            'did' => $resposta->json('did'),
            'handle' => $resposta->json('handle'),
        ];
    }
}
