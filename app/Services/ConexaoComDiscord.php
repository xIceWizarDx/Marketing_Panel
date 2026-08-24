<?php

namespace App\Services;

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Support\Conexao\CanalDeUmGrupoSo;
use App\Support\FalhaDeConexao;
use App\Support\RegistroDeSeguranca;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Conecta um canal do Discord — **por endereço de webhook**.
 *
 * ⭐ É a conexão mais simples do painel inteiro: não há OAuth, não há aplicativo,
 * não há portal. A pessoa cria o webhook no próprio Discord, no canal que ela
 * escolher, e cola o endereço aqui (DEC-141).
 *
 * ⛔ **E o endereço É a credencial**: quem o tem, publica. Por isso ele é partido
 * na hora de guardar — o identificador vai para a conta, o segredo vai para a
 * credencial criptografada, e o endereço inteiro nunca mais aparece.
 */
class ConexaoComDiscord
{
    public const API = 'https://discord.com/api/v10';

    /**
     * ⚠️ Aceita as duas formas que o Discord entrega, e nada além disso:
     * `discord.com/api/webhooks/{id}/{token}` e o mesmo com `discordapp.com`.
     */
    private const FORMATO = '~^https://(?:\w+\.)?discord(?:app)?\.com/api(?:/v\d+)?/webhooks/(\d+)/([\w-]+)~i';

    /**
     * Parte o endereço em identificador e segredo.
     *
     * @return array{id: string, token: string}|null
     */
    public static function partir(string $endereco): ?array
    {
        if (! preg_match(self::FORMATO, trim($endereco), $achado)) {
            return null;
        }

        return ['id' => $achado[1], 'token' => $achado[2]];
    }

    /** Monta o endereço de volta — só o publicador faz isso. */
    public static function enderecoDe(string $id, string $token): string
    {
        return self::API."/webhooks/{$id}/{$token}";
    }

    /**
     * @throws ValidationException
     */
    public function conectar(string $endereco): ContaSocial
    {
        $partes = self::partir($endereco);

        if ($partes === null) {
            throw ValidationException::withMessages([
                'endereco' => 'Esse não parece ser um endereço de webhook do Discord. '.
                    'Ele começa com https://discord.com/api/webhooks/ e você o copia no próprio Discord, '.
                    'em Editar canal → Integrações → Webhooks.',
            ]);
        }

        $webhook = $this->conferir($partes);

        return DB::transaction(function () use ($partes, $webhook) {
            CanalDeUmGrupoSo::garantir(Plataforma::Discord, $partes['id'], 'endereco');

            $conta = ContaSocial::updateOrCreate(
                [
                    'plataforma' => Plataforma::Discord,
                    'identificador_externo' => $partes['id'],
                ],
                [
                    /*
                     * ⭐ O SERVIDOR do Discord, guardado na mesma coluna que a
                     * rede federada usa — e pelo mesmo motivo: sem ele não há
                     * como montar o endereço da mensagem.
                     *
                     * ⚠️ O endereço de uma mensagem é
                     * `discord.com/channels/{servidor}/{canal}/{mensagem}`. Sem
                     * o servidor, o link levava para conversa privada — um link
                     * de prova que não prova nada.
                     */
                    'servidor' => $webhook['servidor'],
                    'nome_exibicao' => $webhook['nome'],
                    'avatar_url' => null,
                    'status' => StatusConta::Ativa,
                    'status_detalhe' => null,
                ]
            );

            $conta->credencial()->updateOrCreate([], [
                /*
                 * ⛔ **O token do webhook é a senha.** Guardado criptografado,
                 * como todo segredo do painel, e nunca devolvido para tela
                 * nenhuma — nem para um administrador impersonando.
                 */
                'access_token' => $partes['token'],
                'refresh_token' => null,
                // ⚠️ Webhook não vence: vale até alguém apagá-lo no Discord.
                'expira_em' => null,
                'escopos' => [],
            ]);

            RegistroDeSeguranca::registrar('rede_conectada', [
                'plataforma' => Plataforma::Discord->value,
                'conta_ulid' => $conta->ulid,
            ]);

            return $conta;
        });
    }

    /**
     * O webhook existe mesmo?
     *
     * ⭐ Conferir na hora custa uma chamada e evita o pior desfecho: conectar um
     * endereço errado, publicar, e a publicação sumir no vazio sem erro nenhum.
     *
     * @param  array{id: string, token: string}  $partes
     * @return array{nome: string, servidor: ?string}
     *
     * @throws ValidationException
     */
    private function conferir(array $partes): array
    {
        try {
            $resposta = Http::timeout(20)->get(self::enderecoDe($partes['id'], $partes['token']));
        } catch (ConnectionException $erro) {
            throw ValidationException::withMessages([
                'endereco' => FalhaDeConexao::explicar($erro, 'Discord'),
            ]);
        }

        if (! $resposta->successful()) {
            throw ValidationException::withMessages([
                'endereco' => 'O Discord não reconheceu esse webhook. '.
                    'Ele pode ter sido apagado — crie outro no canal e cole o endereço novo.',
            ]);
        }

        /*
         * ⚠️ O nome que aparece no painel é o do CANAL quando dá, não o do
         * webhook: "Avisos" diz onde o vídeo vai cair; "Webhook #1" não diz
         * nada.
         */
        $nome = (string) ($resposta->json('name') ?: 'Webhook');
        $canal = $resposta->json('channel_id');

        return [
            'nome' => $canal ? "{$nome} · canal {$canal}" : $nome,
            // ⚠️ Só o webhook devolve o servidor; a mensagem, não. Por isso ele
            // é guardado agora — depois não haveria de onde tirar.
            'servidor' => $resposta->json('guild_id'),
        ];
    }
}
